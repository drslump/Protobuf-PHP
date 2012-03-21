<?php

namespace DrSlump\Protobuf\Codec;

use DrSlump\Protobuf;

/**
 * This codec serializes and unserializes data from/to PHP associative
 * arrays, allowing it to be used as a base for an arbitrary number
 * of different serializations (json, yaml, ini, xml ...).
 *
 * Options:
 *  - lazy (bool) : Use lazy decoding (true by default)
 *  - tags (bool) : Use tag numbers as keys
 *  - strict (bool) : Be strict on non defined required fields
 *
 */
class PhpArray extends Protobuf\CodecAbstract
{
    protected $options = array(
        'lazy'      => true,
        'tags'      => false,
        'strict'    => false
    );

    /**
     * @param \DrSlump\Protobuf\MessageInterface $message
     * @return array
     */
    public function encode(Protobuf\MessageInterface $message)
    {
        return $this->encodeMessage($message);
    }

    /**
     * @param \DrSlump\Protobuf\MessageInterface $message
     * @param array $data
     * @return \DrSlump\Protobuf\Message
     */
    public function decode(Protobuf\MessageInterface $message, $data)
    {
        return $this->decodeMessage($message, $data);
    }

    protected function encodeMessage(Protobuf\MessageInterface $message)
    {
        $descriptor = Protobuf::getRegistry()->getDescriptor($message);

        $strict = $this->getOption('strict');
        $useTagNumber = $this->getOption('tags');

        $data = array();
        foreach ($descriptor->getFields() as $tag=>$field) {

            $empty = !isset($message[$tag]);

            if ($strict && $empty && $field->isRequired() && !$field->hasDefault()) {
                throw new \UnexpectedValueException(
                    'Message ' . get_class($message) . '\'s field tag ' . $tag . '(' . $field->getName() . ') is required but has no value'
                );
            }

            if ($empty && !$field->hasDefault()) {
                continue;
            }

            $key = $useTagNumber ? $field->getNumber() : $field->getName();
            $v = $message[$tag];

            if (NULL === $v) {
                continue;
            }

            if ($field->isRepeated()) {
                // Make sure the value is iterable
                if (!is_array($v) && !($v instanceof \Traversable)) {
                    $v = array($v);
                }

                $value = array();
                foreach ($v as $k=>$vv) {
                    // Skip nullified repeated values
                    if (NULL === $vv) {
                        continue;
                    }
                    $value[] = $this->filterValue($vv, $field);
                }
            } else {
                $value = $this->filterValue($v, $field);
            }

            $data[$key] = $value;
        }

        return $data;
    }

    protected function decodeMessage(Protobuf\MessageInterface $message, $data)
    {
        // Get message descriptor
        $descriptor = Protobuf::getRegistry()->getDescriptor($message);

        $isLazy = $this->getOption('lazy');
        $useTagNumber = $this->getOption('tags');

        foreach ($data as $key=>$v) {

            // Get the field by tag number or name
            $field = $useTagNumber
                   ? $descriptor->getField($key)
                   : $descriptor->getFieldByName($key);

            // Unknown field found
            if (!$field) {
                $unknown = new PhpArray\Unknown($key, gettype($v), $v);
                $message->addUnknown($unknown);
                continue;
            }

            if ($field->isRepeated()) {
                // Make sure the value is an array of values
                $v = is_array($v) && is_int(key($v)) ? $v : array($v);

                // If we are packing lazy values use a LazyRepeat as container
                if ($isLazy && $field->getType() === Protobuf::TYPE_MESSAGE) {
                    $v = new Protobuf\LazyRepeat($v);
                    $v->codec = $this;
                    $v->descriptor = $field;
                } else {
                    foreach ($v as $k=>$vv) {
                        $v[$k] = $this->filterValue($vv, $field);
                    }
                }

            } else {
                $v = $this->filterValue($v, $field);
            }

            $message->initValue($field->getName(), $v);
        }

        return $message;
    }

    public function lazyDecode($field, $value)
    {
        $nested = $field->getReference();
        return $this->decodeMessage(new $nested, $value);
    }

    protected function filterValue($value, Protobuf\Field $field)
    {
        switch ($field->getType()) {
            case Protobuf::TYPE_MESSAGE:
                // Tell apart encoding and decoding
                if ($value instanceof Protobuf\MessageInterface) {
                    return $this->encodeMessage($value);
                } else {
                    // Check if we are decoding in lazy mode
                    if ($this->getOption('lazy')) {
                        $lazy = new Protobuf\LazyValue();
                        $lazy->codec = $this;
                        $lazy->descriptor = $field;
                        $lazy->value = $value;
                        return $lazy;
                    } else {
                        $nested = $field->getReference();
                        return $this->decodeMessage(new $nested, $value);
                    }
                }

            case Protobuf::TYPE_BOOL:
                if ($value === true || $value === false) {
                    return $value;
                }
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);

            case Protobuf::TYPE_STRING:
            case Protobuf::TYPE_BYTES:
                return (string)$value;
            case Protobuf::TYPE_FLOAT:
            case Protobuf::TYPE_DOUBLE:
                return filter_var($value, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
            // Assume the rest are ints
            default:
                return filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
        }
    }
}
