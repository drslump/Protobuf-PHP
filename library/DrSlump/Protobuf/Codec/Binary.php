<?php

namespace DrSlump\Protobuf\Codec;

use DrSlump\Protobuf;

class Binary implements Protobuf\CodecInterface
{
    const WIRE_VARINT      = 0;
    const WIRE_FIXED64     = 1;
    const WIRE_LENGTH      = 2;
    const WIRE_GROUP_START = 3;
    const WIRE_GROUP_END   = 4;
    const WIRE_FIXED32     = 5;
    const WIRE_UNKNOWN     = -1;

    /**
     * @param \DrSlump\Protobuf\Message $message
     * @return string
     */
    public function encode(Protobuf\Message $message)
    {
        return $this->encodeMessage($message);
    }

    /**
     * @param String|Message $message
     * @param String $data
     * @return \DrSlump\Protobuf\Message
     */
    public function decode(Protobuf\Message $message, $data)
    {
        return $this->decodeMessage($message, $data);
    }


    protected function encodeMessage(Protobuf\Message $message)
    {
        $writer = new Binary\Writer();

        $descriptor = $message->descriptor();

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
            $key = $tag << 3 | $this->getWireType($type, null);

            $value = $message->_get($tag);

            // @todo Support PACKED fields
            if ($field->isRepeated()) {
                foreach($value as $val) {
                    if ($type !== Protobuf::TYPE_MESSAGE) {
                        $writer->varint($key);
                        $this->encodeSimpleType($writer, $type, $val);
                    } else {
                        $writer->varint($key);
                        $data = $this->encodeMessage($val);
                        $writer->varint(strlen($data));
                        $writer->write($data);
                    }
                }
            } else {
                if ($type !== Protobuf::TYPE_MESSAGE) {
                    $writer->varint($key);
                    $this->encodeSimpleType($writer, $type, $value);
                } else {
                    $writer->varint($key);
                    $data = $this->encodeMessage($value);
                    $writer->varint(strlen($data));
                    $writer->write($data);
                }
            }
        }

        return $writer->getContents();
    }

    protected function encodeSimpleType($writer, $type, $value)
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


    protected function decodeMessage(\DrSlump\Protobuf\Message $message, $data)
    {
        /** @var $message \DrSlump\Protobuf\Message */
        /** @var $descriptor \DrSlump\Protobuf\Descriptor */

        // Create a binary reader for the data
        $reader = new Protobuf\Codec\Binary\Reader($data);

        // Get message descriptor
        $descriptor = $message->descriptor();

        while (!$reader->eof()) {

            // Get initial varint with tag number and wire type
            $key = $reader->varint();
            if ($reader->eof()) break;

            $wire = $key & 0x7;
            $tag = $key >> 3;

            // Find the matching field for the tag number
            $field = $descriptor->getField($tag);
            if (!$field) {
                $data = $this->decodeUnknown($reader, $wire);
                $unknown = new Binary\Unknown($tag, $wire, $data);
                $message->addUnknown($unknown);
                continue;
            }

            $type = $field->getType();

            // Check if we are dealing with a packaged structure
            if ($wire === self::WIRE_LENGTH && $this->isPackable($type)) {
                $length = $reader->varint();
                $ofs = $reader->pos();
                $read = 0;
                while ($read < $length) {
                    $item = $this->decodeSimpleType($reader, $type, self::WIRE_VARINT);
                    $read = $reader->pos() - $ofs;
                    $message->_add($tag, $item);
                }

            } else {

                // Assert wire and type match
                $this->assertWireType($wire, $type);

                // Check if it's a sub-message
                if ($type === Protobuf::TYPE_MESSAGE) {
                    $submessage = $field->getReference();
                    $submessage = new $submessage;

                    $length = $this->decodeSimpleType($reader, Protobuf::TYPE_INT64, self::WIRE_VARINT);
                    $data = $reader->read($length);

                    $value = $this->decodeMessage($submessage, $data);
                } else {
                    $value = $this->decodeSimpleType($reader, $type, $wire);
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

    protected function isPackable($type)
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

    protected function decodeUnknown($reader, $wire)
    {
        switch ($wire) {
            case self::WIRE_VARINT:
                return $reader->varint();
            case self::WIRE_LENGTH:
                $length = $reader->varint();
                return $reader->read($length);
            case self::WIRE_FIXED32:
                return $reader->fixed32();
            case self::WIRE_FIXED64:
                return $reader->fixed64;
            case self::WIRE_GROUP_START:
            case self::WIRE_GROUP_END:
                throw new \Exception('Groups are deprecated in Protocol Buffers and unsupported by this library');
            default:
                throw new \Exception('Unsupported wire type (' . $wire . ') while consuming unknown field');
        }
    }

    protected function assertWireType($wire, $type)
    {
        $expected = $this->getWireType($type, $wire);
        if ($wire !== $expected) {
            throw new \Exception('Expected wire type ' . $expected . ' but got ' . $wire . ' for type ' . $type);
        }
    }

    protected function getWireType($type, $wire)
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
                return self::WIRE_VARINT;
            case Protobuf::TYPE_FIXED64:
            case Protobuf::TYPE_SFIXED64:
            case Protobuf::TYPE_DOUBLE:
                return self::WIRE_FIXED64;
            case Protobuf::TYPE_STRING:
            case Protobuf::TYPE_BYTES:
            case Protobuf::TYPE_MESSAGE:
                return self::WIRE_LENGTH;
            case Protobuf::TYPE_FIXED32:
            case Protobuf::TYPE_SFIXED32:
            case Protobuf::TYPE_FLOAT:
                return self::WIRE_FIXED32;
            default:
                // Unknown fields just return the reported wire type
                return $wire;
        }
    }

    protected function decodeSimpleType($reader, $type, $wireType)
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
                    case self::WIRE_VARINT:
                        return $reader->varint();
                    case self::WIRE_FIXED32:
                        return $reader->fixed32();
                    case self::WIRE_FIXED64:
                        return $reader->fixed64();
                    case self::WIRE_LENGTH:
                        $length = $reader->varint();
                        return $reader->read($length);
                    case self::WIRE_GROUP_START:
                    case self::WIRE_GROUP_END:
                        throw new \Exception('Group is deprecated and not supported');
                    default:
                        throw new \Exception('Unsupported wire type number ' . $wireType);
                }
        }

    }

}
