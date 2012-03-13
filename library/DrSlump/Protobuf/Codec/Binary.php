<?php

namespace DrSlump\Protobuf\Codec;

use DrSlump\Protobuf;

/**
 * Acts as a proxy to the actual binary codec implementation, either using the
 * extension or the native PHP implementation
 */
class Binary implements Protobuf\CodecInterface
{
    /** @var \DrSlump\Protobuf\CodecInterface */
    protected $_impl;

    public function __construct($lazy = true)
    {
        if (extension_loaded('protobuf')) {
            $this->_impl = new Binary\Extension($lazy);
        } else {
            $this->_impl = new Binary\Native($lazy);
        }
    }

    public function encode(Protobuf\MessageInterface $message)
    {
        return $this->_impl->encode($message);
    }

    public function decode(Protobuf\MessageInterface $message, $data)
    {
        return $this->_impl->decode($message, $data);
    }
}
