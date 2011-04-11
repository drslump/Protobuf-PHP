<?php

namespace DrSlump\Protobuf\Codec;

use DrSlump\Protobuf;

class JsonHashTag implements Protobuf\CodecInterface
{
    static public function encode(Protobuf\Message $message)
    {
        $data = self::encodeMessage($message);
        return json_encode($data);
    }

    static public function encodeMessage(Protobuf\Message $message)
    {
        $descriptor = $message::descriptor();

        $data = new \stdClass();
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

            $number = $field->getNumber();
            $value = $message->_get($tag);

            if ($field->isRepeated()) {
                $data->$number = array();
                foreach ($value as $val) {
                    if ($field->getType() !== Protobuf::TYPE_MESSAGE) {
                        $data->{$number}[] = $val;
                    } else {
                        $data->{$number}[] = self::encodeMessage($val);
                    }
                }
            } else {
                if ($field->getType() === Protobuf::TYPE_MESSAGE) {
                    $data->$number = self::encodeMessage($value);
                } else {
                    $data->$number = $value;
                }
            }
        }

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

        foreach ($data as $k=>$v) {
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

}
