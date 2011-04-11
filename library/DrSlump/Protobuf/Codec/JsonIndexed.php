<?php

namespace DrSlump\Protobuf\Codec;

use DrSlump\Protobuf;

class JsonIndexed implements Protobuf\CodecInterface
{
    static public function encode(Protobuf\Message $message)
    {
        $data = self::encodeMessage($message);
        return json_encode($data);
    }

    static public function encodeMessage(Protobuf\Message $message)
    {
        $descriptor = $message::descriptor();

        $index = '';
        $data = array();
        foreach ($descriptor->getFields() as $tag=>$field) {

            $empty = !$message->_has($tag);
            if ($field->isRequired() && $empty) {
                throw new \RuntimeException(
                    'Message ' . get_class($message) . ' field tag ' . $tag . ' is required but has not value'
                );
            }

            if ($empty) {
                continue;
            }

            $index .= self::i2c($tag + 48);

            $value = $message->_get($tag);

            if ($field->isRepeated()) {
                $repeats = array();
                foreach ($value as $val) {
                    if ($field->getType() !== Protobuf::TYPE_MESSAGE) {
                        $repeats[] = $val;
                    } else {
                        $repeats[] = self::encodeMessage($val);
                    }
                }
                $data[] = $repeats;
            } else {
                if ($field->getType() === Protobuf::TYPE_MESSAGE) {
                    $data[] = self::encodeMessage($value);
                } else {
                    $data[] = $value;
                }
            }
        }

        // Insert the index at first element
        array_unshift($data, $index);

        return $data;
    }

    static public function decode($message, $data)
    {
        $data = json_decode($data);
        return self::decodeMessage($data, $message);
    }

    static public function decodeMessage($data, $message)
    {
        // If an instance was not given create one
        // @todo check message class is valid
        if (is_string($message)) {
            $message = new $message();
        }

        // Get message descriptor
        $descriptor = $message::descriptor();

        preg_match_all('/./u', $data[0], $chars);

        $chars = $chars[0];
        for ($i=1; $i<count($data); $i++) {

            $k = self::c2i($chars[$i-1]) - 48;
            $v = $data[$i];

            $field = $descriptor->getField($k);

            if (NULL === $field) {
                // Unknown
                $unknown = new Json\Unknown($k, gettype($v), $v);
                $message->addUnknown($unknown);
                continue;
            }

            $message->_set($k, $v);

            if ($field->getType() === Protobuf::TYPE_MESSAGE) {
                $nested = $field->getReference();
                $v = self::decodeMessage($v, $nested);
            }

            if ($field->isRepeated()) {
                $message->_add($k, $v);
            } else {
                $message->_set($k, $v);
            }
        }

        return $message;
    }

    static protected function i2c($codepoint)
    {
        return $codepoint < 128
               ? chr($codepoint)
               : html_entity_decode("&#$codepoint;", ENT_NOQUOTES, 'UTF-8');
    }

    static protected function c2i($char)
    {
        $value = ord($char[0]);
        if ($value < 128) return $value;

        if ($value < 224) {
            return (($value % 32) * 64) + (ord($char[1]) % 64);
        } else {
            return (($value % 16) * 4096) +
                   ((ord($char[1]) % 64) * 64) +
                   (ord($char[2]) % 64);
        }
    }

}
