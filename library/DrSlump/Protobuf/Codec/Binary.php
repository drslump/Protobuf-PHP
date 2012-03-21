<?php

namespace DrSlump\Protobuf\Codec;

use DrSlump\Protobuf;

/**
 * Acts as a proxy to the actual binary codec implementation, either using the
 * extension or the native PHP implementation
 */
class Binary extends Protobuf\CodecAbstract
    implements Protobuf\CodecInterface
{
    protected $options = array(
        'extension' => 'protobuf'
    );

    /** @var \DrSlump\Protobuf\CodecInterface */
    protected $_impl;


    public function __construct($options = array())
    {
        parent::__construct($options);

        if (extension_loaded($this->getOption('extension'))) {
            $this->_impl = new Binary\Extension($options);
        } else {
            $this->_impl = new Binary\Native($options);
        }
    }

    public function setOption($name, $value)
    {
        if ($this->_impl) {
            $this->_impl->setOption($name, $value);
        } else {
            parent::setOption($name, $value);
        }
    }

    public function getOption($name, $default = null)
    {
        if ($this->_impl) {
            return $this->_impl->getOption($name, $default);
        } else {
            return parent::getOption($name, $default);
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
