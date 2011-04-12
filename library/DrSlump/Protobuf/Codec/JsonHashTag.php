<?php

namespace DrSlump\Protobuf\Codec;

use DrSlump\Protobuf;

class JsonHashTag extends Json
    implements Protobuf\CodecInterface
{

    /**
     * @static
     * @return Binary
     */
    static public function getInstance()
    {
        static $instance;

        if (NULL === $instance) {
            $instance = new self();
        }

        return $instance;
    }

    public function encodeMessage(Protobuf\Message $message)
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


    public function decodeMessage(Protobuf\Message $message, $data)
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
                $nested = new $nested;
                $v = $this->decodeMessage($nested, $v);
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
