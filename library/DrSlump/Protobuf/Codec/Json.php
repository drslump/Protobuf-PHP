<?php

namespace DrSlump\Protobuf\Codec;

use DrSlump\Protobuf;

class Json implements Protobuf\CodecInterface
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

            $name = $field->getName();
            $value = $message->_get($tag);

            if ($field->isRepeated()) {
                $data->$name = array();
                foreach ($value as $val) {
                    if ($field->getType() !== Protobuf::TYPE_MESSAGE) {
                        $data->{$name}[] = $val;
                    } else {
                        $data->{$name}[] = self::encodeMessage($val);
                    }
                }
            } else {
                if ($field->getType() === Protobuf::TYPE_MESSAGE) {
                    $data->$name = self::encodeMessage($value);
                } else {
                    $data->$name = $value;
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
            $tag = null;
            foreach ($descriptor->getFields() as $field) {
                if ($field->getName() === $k) {
                    $tag = $field->getNumber();
                    break;
                }
            }

            if ($tag === NULL) {
                // Unknown
                $unknown = new Json\Unknown($tag, gettype($v), $v);
                $message->addUnknown($unknown);
                continue;
            }

            $message->_set($tag, $v);

            if ($field->getType() === Protobuf::TYPE_MESSAGE) {
                $nested = $field->getReference();
                $v = self::decodeMessage($v, $nested);
            }

            if ($field->isRepeated()) {
                $message->_add($tag, $v);
            } else {
                $message->_set($tag, $v);
            }
        }

        return $message;
    }

}
