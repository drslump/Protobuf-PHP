<?php

namespace DrSlump\Protobuf\Codec;

use DrSlump\Protobuf;

class Json implements Protobuf\CodecInterface
{
    /**
     * @param \DrSlump\Protobuf\Message $message
     * @return string
     */
    public function encode(Protobuf\Message $message)
    {
        $data = $this->encodeMessage($message);
        return json_encode($data);
    }

    /**
     * @param \DrSlump\Protobuf\Message $message
     * @param String $data
     * @return \DrSlump\Protobuf\Message
     */
    public function decode(Protobuf\Message $message, $data)
    {
        $data = json_decode($data);
        return $this->decodeMessage($message, $data);
    }

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

            $name = $field->getName();
            $value = $message->_get($tag);

            if ($field->isRepeated()) {
                $data->$name = array();
                foreach ($value as $val) {
                    if ($field->getType() !== Protobuf::TYPE_MESSAGE) {
                        $data->{$name}[] = $val;
                    } else {
                        $data->{$name}[] = $this->encodeMessage($val);
                    }
                }
            } else {
                if ($field->getType() === Protobuf::TYPE_MESSAGE) {
                    $data->$name = $this->encodeMessage($value);
                } else {
                    $data->$name = $value;
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


            if ($field->getType() === Protobuf::TYPE_MESSAGE) {

                $nested = $field->getReference();
                if ($field->isRepeated()) {
                    foreach($v as $vv) {
                        $obj = $this->decodeMessage(new $nested, $vv);
                        $message->_add($tag, $obj);
                    }
                } else {
                    $obj = new $nested;
                    $v = $this->decodeMessage($obj, $v);
                    $message->_set($tag, $v);
                }

            } else {
                $message->_set($tag, $v);
            }
        }

        return $message;
    }

}
