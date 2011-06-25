<?php

namespace DrSlump\Protobuf\Codec;

use DrSlump\Protobuf;

class JsonTagMap extends Json
    implements Protobuf\CodecInterface
{
    protected function encodeMessage(Protobuf\Message $message)
    {
        $descriptor = $message->descriptor();

        $data =  array();
        foreach ($descriptor->getFields() as $tag=>$field) {

            $empty = !$message->_has($tag);
            if ($field->isRequired() && $empty) {
                throw new \UnexpectedValueException(
                    'Message ' . get_class($message) . '\'s field tag ' . $tag . '(' . $field->getName() . ') is required but has no value'
                );
            }

            if ($empty) {
                continue;
            }

            $value = $message->_get($tag);

            if ($field->getType() === Protobuf::TYPE_MESSAGE) {
                $value = $field->isRepeated()
                       ? array_map(array($this, 'encodeMessage'), $value)
                       : $this->encodeMessage($value);
            }

            $number = $field->getNumber();
            $data[$number] = $value;
        }

        return $data;
    }


    protected function decodeMessage(Protobuf\Message $message, $data)
    {
        // Get message descriptor
        $descriptor = $message->descriptor();

        foreach ($data as $k=>$v) {
            $field = $descriptor->getField($k);

            if (NULL === $field) {
                // Unknown
                $unknown = new PhpArray\Unknown($k, gettype($v), $v);
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
