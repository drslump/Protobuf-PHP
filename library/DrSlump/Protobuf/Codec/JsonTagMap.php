<?php

namespace DrSlump\Protobuf\Codec;

use DrSlump\Protobuf;

class JsonTagMap extends Json
    implements Protobuf\CodecInterface
{
    protected function encodeMessage(Protobuf\Message $message)
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


    protected function decodeMessage(Protobuf\Message $message, $data)
    {
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

            if ($field->getType() === Protobuf::TYPE_MESSAGE) {
                $nested = $field->getReference();
                if ($field->isRepeated()) {
                    foreach($v as $vv) {
                        $obj = $this->decodeMessage(new $nested, $vv);
                        $message->_add($k, $obj);
                    }
                } else {
                    $obj = $this->decodeMessage(new $nested, $v);
                    $message->_set($k, $obj);
                }
            } else {
                $message->_set($k, $v);
            }
        }

        return $message;
    }

}
