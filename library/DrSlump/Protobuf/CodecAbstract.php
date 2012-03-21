<?php

namespace DrSlump\Protobuf;

abstract class CodecAbstract implements CodecInterface
{
    protected $options = array();

    public function __construct($options = array())
    {
        if (!empty($options)) {
            $this->setOptions($options);
        }
    }

    public function setOption($name, $value)
    {
        $this->options[$name] = $value;
    }

    public function setOptions(array $options)
    {
        foreach ($options as $name=>$value) {
            $this->setOption($name, $value);
        }
    }

    public function getOption($name, $default = null)
    {
        return isset($this->options[$name]) ? $this->options[$name] : $default;
    }

    public function getOptions()
    {
        return $this->options;
    }
}
