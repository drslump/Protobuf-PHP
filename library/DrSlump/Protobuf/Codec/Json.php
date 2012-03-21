<?php

namespace DrSlump\Protobuf\Codec;

use DrSlump\Protobuf;

/**
 * This codec serializes and unserializes from/to Json strings
 * where the keys represent the field's name.
 *
 * It makes use of the PhpArray codec to do the heavy work to just
 * take care of converting the array to/from Json strings.
 */
class Json extends PhpArray
    implements Protobuf\CodecInterface
{
    protected $options = array(
        'lazy'      => true,
        'tags'      => false,
        'strict'    => true,
    );

    /**
     * @param \DrSlump\Protobuf\MessageInterface $message
     * @return string
     */
    public function encode(Protobuf\MessageInterface $message)
    {
        $data = $this->encodeMessage($message);
        return json_encode($data);
    }

    /**
     * @param \DrSlump\Protobuf\MessageInterface $message
     * @param string $data
     * @return \DrSlump\Protobuf\MessageInterface
     */
    public function decode(Protobuf\MessageInterface $message, $data)
    {
        $data = json_decode($data, true);
        return $this->decodeMessage($message, $data);
    }

}
