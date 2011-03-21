<?php

namespace DrSlump\Protobuf\Codec;

use DrSlump\Protobuf;

class Binary implements Protobuf\CodecInterface
{
    static public function encode(Protobuf\Message $message)
    {
        // Create a binary writer for the data
        $writer = new Protobuf\Codec\Binary\Writer();

        // Encode the message
        $data = self::encodeMessage($message);
        if (strlen($data)) {
            $writer->write($data);
        }
        return $writer->getContents();
    }

    static public function encodeMessage(Protobuf\Message $message)
    {
        $writer = new Binary\Writer();

        $descriptor = $message::descriptor();

        foreach ($descriptor->getFields() as $tag=>$field) {

            $empty = !$message->_has($tag);

            if ($field->isRequired() && $empty) {
                throw new \RuntimeException(
                    'Message ' . get_class($message) . ' field tag ' . $tag . ' is required but has no value'
                );
            }

            // Skip empty fields
            if ($empty) {
                continue;
            }

            $type = $field->getType();

            // Compute key with tag number and wire type
            $key = $tag << 3 | self::getWireType($type, null);
            $writer->varint($key);

            $value = $message->_get($tag);

            // @todo Support PACKED fields
            if ($field->isRepeated()) {
                $data = '';
                foreach($value as $val) {
                    if ($type !== Protobuf::TYPE_MESSAGE) {
                       self::encodeSimpleType($writer, $type, $val);
                    } else {
                       $data .= self::encodeMessage($val);
                    }
                }

                if (strlen($data) > 0) {
                    $writer->varint(strlen($data));
                    $writer->write($data);
                }
            } else {
                if ($type !== Protobuf::TYPE_MESSAGE) {
                    self::encodeSimpleType($writer, $type, $message->_get($tag));
                } else {
                    $data = self::encodeMessage($field->getReference());
                    $writer->varint(strlen($data));
                    $writer->write($data);
                }
            }
        }

        return $writer->getContents();
    }

    static public function encodeSimpleType($writer, $type, $value)
    {
        switch ($type) {
            case Protobuf::TYPE_INT64:
            case Protobuf::TYPE_UINT64:
            case Protobuf::TYPE_INT32:
            case Protobuf::TYPE_UINT32:
                $writer->varint($value);
                break;

            case Protobuf::TYPE_SINT32: // ZigZag
            case Protobuf::TYPE_SINT64: // ZigZag
                $value = ($value >> 1) ^ (-($value & 1));
                $writer->varint($value);
                break;

            case Protobuf::TYPE_DOUBLE:
                $writer->double($value);
                break;
            case Protobuf::TYPE_FIXED64:
                $writer->fixed64($value);
                break;
            case Protobuf::TYPE_SFIXED64:
                $writer->sFixed64($value);
                break;

            case Protobuf::TYPE_FLOAT:
                $writer->float($value);
                break;
            case Protobuf::TYPE_FIXED32:
                $writer->fixed32($value);
                break;
            case Protobuf::TYPE_SFIXED32:
                $writer->sFixed32($value);
                break;

            case Protobuf::TYPE_BOOL:
                $writer->varint($value ? 1 : 0);
                break;

            case Protobuf::TYPE_STRING:
            case Protobuf::TYPE_BYTES:
                $writer->varint(strlen($value));
                $writer->write($value);
                break;

            case Protobuf::TYPE_MESSAGE:
                // Messages are not supported in this method
                return null;

            case Protobuf::TYPE_ENUM:
                $writer->varint($value);
                break;

            default:
                throw new \Exception('Unknown field type ' . $type);
        }
    }

    static public function decode($message, $data)
    {
        // Create a binary reader for the data
        $reader = new Protobuf\Codec\Binary\Reader($data);

        // Decode the message
        return self::decodeMessage($reader, $message);
    }

    static public function decodeMessage(Protobuf\Codec\Binary\Reader $reader, $message, $limit = null)
    {
        // If an instance was not given create one
        // @todo check message class is valid
        if (is_string($message)) {
            $message = new $message();
        }

        /** @var $message \DrSlump\Protobuf\Message */
        /** @var $descriptor \DrSlump\Protobuf\Descriptor */

        // Get message descriptor
        $descriptor = $message::descriptor();

        $current_pos = $reader->pos();
        while (!$reader->eof()) {

            if ($limit !== NULL && $reader->pos() - $current_pos >= $limit) {
                break;
            }

            // Get initial varint with tag number and wire type
            $key = $reader->varint();
            if ($reader->eof()) break;

            $wire = $key & 0x7;
            $tag = $key >> 3;

            // Find the matching field for the tag number
            $field = $descriptor->getField($tag);
            if (!$field) {
                fputs(STDERR, 'Unknown field! ' . $tag . PHP_EOL);
                $data = self::decodeUnknown($reader, $wire);
                $unknown = new Binary\Unknown($tag, $wire, $data);
                $message->addUnknown($unknown);
                continue;
            }

            $type = $field->getType();

            //echo "Tag: $tag - Field: " . $field->getName() . " - Type: $type\n";

            // Check if we are dealing with a packaged structure
            if ($wire === Protobuf::WIRE_LENGTH && self::isPackable($type)) {
                $length = $reader->varint();
                $ofs = $reader->pos();
                $read = 0;
                while ($read < $length) {
                    $item = self::decodeSimpleType($reader, $type, Protobuf::WIRE_VARINT);
                    $read = $reader->pos() - $ofs;
                    $message->_add($tag, $item);
                }

            } else {

                // Assert wire and type match
                self::assertWireType($wire, $type);

                // Check if it's a sub-message
                if ($type === Protobuf::TYPE_MESSAGE) {
                    $submessage = $field->getReference();
                    $expected_length = self::decodeSimpleType($reader, Protobuf::TYPE_INT64, Protobuf::WIRE_VARINT);
                    $initial_pos = $reader->pos();
                    $value = self::decodeMessage($reader, $submessage, $expected_length);
                    $actual_length = $reader->pos() - $initial_pos;
                    if ($actual_length !== $expected_length) {
                        throw new \Exception("Malformed message. Decoded length ($actual_length) doesn't match reported one ($expected_length)");
                    }
                } else {
                    $value = self::decodeSimpleType($reader, $type, $wire);
                }

                // Support non-packed repeated fields
                if ($field->isRepeated()) {
                    $message->_add($tag, $value);
                } else {
                    $message->_set($tag, $value);
                }
            }
        }

        return $message;
    }

    static public function isPackable($type)
    {
        return in_array($type, array(
            Protobuf::TYPE_INT64,
            Protobuf::TYPE_UINT64,
            Protobuf::TYPE_INT32,
            Protobuf::TYPE_UINT32,
            Protobuf::TYPE_SINT32,
            Protobuf::TYPE_SINT64,
            Protobuf::TYPE_DOUBLE,
            Protobuf::TYPE_FIXED64,
            Protobuf::TYPE_SFIXED64,
            Protobuf::TYPE_FLOAT,
            Protobuf::TYPE_FIXED32,
            Protobuf::TYPE_SFIXED32,
            Protobuf::TYPE_BOOL,
            Protobuf::TYPE_ENUM
        ));
    }

    static public function decodeUnknown($reader, $wire)
    {
        switch ($wire) {
            case Protobuf::WIRE_VARINT:
                return $reader->varint();
            case Protobuf::WIRE_LENGTH:
                $length = $reader->varint();
                return $reader->read($length);
            case Protobuf::WIRE_FIXED32:
                return $reader->fixed32();
            case Protobuf::WIRE_FIXED64:
                return $reader->fixed64;
            case Protobuf::WIRE_GROUP_START:
            case Protobuf::WIRE_GROUP_END:
                throw new \Exception('Groups are deprecated in Protocol Buffers and unsupported by this library');
            default:
                throw new \Exception('Unsupported wire type (' . $wire . ') while consuming unknown field');
        }
    }

    static public function assertWireType($wire, $type)
    {
        $expected = self::getWireType($type, $wire);
        if ($wire !== $expected) {
            throw new \Exception('Expected wire type ' . $expected . ' but got ' . $wire . ' for type ' . $type);
        }
    }

    static public function getWireType($type, $wire)
    {
        switch ($type) {
            case Protobuf::TYPE_INT32:
            case Protobuf::TYPE_INT64:
            case Protobuf::TYPE_UINT32:
            case Protobuf::TYPE_UINT64:
            case Protobuf::TYPE_SINT32:
            case Protobuf::TYPE_SINT64:
            case Protobuf::TYPE_BOOL:
            case Protobuf::TYPE_ENUM:
                return Protobuf::WIRE_VARINT;
            case Protobuf::TYPE_FIXED64:
            case Protobuf::TYPE_SFIXED64:
            case Protobuf::TYPE_DOUBLE:
                return Protobuf::WIRE_FIXED64;
            case Protobuf::TYPE_STRING:
            case Protobuf::TYPE_BYTES:
            case Protobuf::TYPE_MESSAGE:
                return Protobuf::WIRE_LENGTH;
            case Protobuf::TYPE_FIXED32:
            case Protobuf::TYPE_SFIXED32:
            case Protobuf::TYPE_FLOAT:
                return Protobuf::WIRE_FIXED32;
            default:
                // Unknown fields just return the reported wire type
                return $wire;
        }
    }

    static public function decodeSimpleType($reader, $type, $wireType)
    {
        switch ($type) {
            case Protobuf::TYPE_INT64:
            case Protobuf::TYPE_UINT64:
            case Protobuf::TYPE_INT32:
            case Protobuf::TYPE_UINT32:
                return $reader->varint();

            case Protobuf::TYPE_SINT32: // ZigZag
                $number = $reader->varint();
                return ($number << 1) ^ ($number >> 31);
            case Protobuf::TYPE_SINT64: // ZigZag
                $number = $reader->varint();
                return ($number << 1) ^ ($number >> 63);

            case Protobuf::TYPE_DOUBLE:
                return $reader->double();
            case Protobuf::TYPE_FIXED64:
                return $reader->fixed64();
            case Protobuf::TYPE_SFIXED64:
                return $reader->sFixed64();

            case Protobuf::TYPE_FLOAT:
                return $reader->float();
            case Protobuf::TYPE_FIXED32:
                return $reader->fixed32();
            case Protobuf::TYPE_SFIXED32:
                return $reader->sFixed32();

            case Protobuf::TYPE_BOOL:
                return (bool)$reader->varint();

            case Protobuf::TYPE_STRING:
                $length = $reader->varint();
                return $reader->read($length);

            case Protobuf::TYPE_MESSAGE:
                // Messages are not supported in this method
                return null;

            case Protobuf::TYPE_BYTES:
                $length = $reader->varint();
                return $reader->read($length);

            case Protobuf::TYPE_ENUM:
                return $reader->varint();

            default:
                // Unknown type, follow wire type rules
                switch ($wireType) {
                    case Protobuf::WIRE_VARINT:
                        return $reader->varint();
                    case Protobuf::WIRE_FIXED32:
                        return $reader->fixed32();
                    case Protobuf::WIRE_FIXED64:
                        return $reader->fixed64();
                    case Protobuf::WIRE_LENGTH:
                        $length = $reader->varint();
                        return $reader->read($length);
                    case Protobuf::WIRE_GROUP_START:
                    case Protobuf::WIRE_GROUP_END:
                        throw new \Exception('Group is deprecated and not supported');
                    default:
                        throw new \Exception('Unsupported wire type number ' . $wireType);
                }
        }

    }

}
