<?php

namespace DrSlump\Protobuf\Codec;

use DrSlump\Protobuf;

class PhpArray implements Protobuf\CodecInterface
{
    /**
     * @param \DrSlump\Protobuf\Message $message
     * @return array
     */
    public function encode(Protobuf\Message $message)
    {
        return $this->encodeMessage($message);
    }

    /**
     * @param \DrSlump\Protobuf\Message $message
     * @param array $data
     * @return \DrSlump\Protobuf\Message
     */
    public function decode(Protobuf\Message $message, $data)
    {
        return $this->decodeMessage($message, $data);
    }

    protected function encodeMessage(Protobuf\Message $message)
    {
        $descriptor = $message->descriptor();

        $data = array();
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

            $name = $field->getName();
            $value = $message->_get($tag);

            if ($field->getType() === Protobuf::TYPE_MESSAGE) {
                $value = $field->isRepeated()
                       ? array_map(array($this, 'encodeMessage'), $value)
                       : $this->encodeMessage($value);
            }

            $data[$name] = $value;
        }

        return $data;
    }

    protected function decodeMessage(Protobuf\Message $message, $data)
    {
        // Get message descriptor
        $descriptor = $message->descriptor();

        foreach ($data as $k=>$v) {
            $tag = null;
            $field = null;
            foreach ($descriptor->getFields() as $field) {
                if ($field->getName() === $k) {
                    $tag = $field->getNumber();
                    break;
                }
            }

            if ($tag === NULL) {
                // Unknown
                $unknown = new PhpArray\Unknown($tag, gettype($v), $v);
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
