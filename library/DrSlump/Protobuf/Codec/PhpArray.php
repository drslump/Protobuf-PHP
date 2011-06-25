<?php

namespace DrSlump\Protobuf\Codec;

use DrSlump\Protobuf;

/**
 * This codec serializes and unserializes data from/to PHP associative
 * arrays, allowing it to be used as a base for an arbitrary number
 * of different serializations (json, yaml, ini, xml ...).
 *
 */
class PhpArray implements Protobuf\CodecInterface
{
    /** @var bool */
    protected $useTagNumber = false;

    /**
     * Tells the codec to expect the array keys to contain the
     * field's tag number instead of the name.
     *
     * @param bool $useIt
     */
    public function useTagNumberAsKey($useIt = true)
    {
        $this->useTagNumber = $useIt;
    }

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

            $key = $this->useTagNumber ? $field->getNumber() : $field->getName();
            $value = $message->_get($tag);

            if ($field->getType() === Protobuf::TYPE_MESSAGE) {
                $value = $field->isRepeated()
                       ? array_map(array($this, 'encodeMessage'), $value)
                       : $this->encodeMessage($value);
            }

            $data[$key] = $value;
        }

        return $data;
    }

    protected function decodeMessage(Protobuf\Message $message, $data)
    {
        // Get message descriptor
        $descriptor = $message->descriptor();

        foreach ($data as $key=>$v) {

            // Search for the field with the name
            if (!$this->useTagNumber) {
                $field = null;
                foreach ($descriptor->getFields() as $f) {
                    if ($f->getName() === $key) {
                        $field = $f;
                        break;
                    }
                }
            // Get the field by tag number
            } else {
                $field = $descriptor->getField($key);
            }

            // Unknown field found
            if (!$field) {
                $unknown = new PhpArray\Unknown($key, gettype($v), $v);
                $message->addUnknown($unknown);
                continue;
            }

            $key = $field->getNumber();

            if ($field->getType() === Protobuf::TYPE_MESSAGE) {
                $nested = $field->getReference();
                if ($field->isRepeated()) {
                    foreach($v as $vv) {
                        $obj = $this->decodeMessage(new $nested, $vv);
                        $message->_add($key, $obj);
                    }
                } else {
                    $obj = new $nested;
                    $v = $this->decodeMessage($obj, $v);
                    $message->_set($key, $v);
                }
            } else {
                $message->_set($key, $v);
            }
        }

        return $message;
    }

}
