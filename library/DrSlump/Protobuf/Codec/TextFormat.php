<?php

namespace DrSlump\Protobuf\Codec;

use DrSlump\Protobuf;

class TextFormat implements Protobuf\CodecInterface
{
    /**
     * @param \DrSlump\Protobuf\Message $message
     * @return string
     */
    public function encode(Protobuf\Message $message)
    {
        return $this->encodeMessage($message);
    }

    /**
     *
     * @throw \DrSlump\Protobuf\Exception - Decoding is not supported
     * @param \DrSlump\Protobuf\Message $message
     * @param String $data
     * @return \DrSlump\Protobuf\Message
     */
    public function decode(Protobuf\Message $message, $data)
    {
        throw new Protobuf\Exception('TextFormat codec does not support decoding');
    }

    protected function encodeMessage(Protobuf\Message $message, $level = 0)
    {
        $indent = str_repeat('  ', $level);

        $descriptor = $message::descriptor();

        $data = '';
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
                foreach ($value as $val) {
                    if ($field->getType() !== Protobuf::TYPE_MESSAGE) {
                        $data .= $indent . $name . ': ' . json_encode($val) . "\n";
                    } else {
                        $data .= $indent . $name . " {\n";
                        $data .= $this->encodeMessage($val, $level+1);
                        $data .= $indent . "}\n";
                    }
                }
            } else {
                if ($field->getType() === Protobuf::TYPE_MESSAGE) {
                    $data .= $indent . $name . " {\n";
                    $data .= $this->encodeMessage($value, $level+1);
                    $data .= $indent . "}\n";
                } else {
                    $data .= $indent . $name . ': ' . json_encode($value) . "\n";
                }
            }
        }

        return $data;
    }
}

