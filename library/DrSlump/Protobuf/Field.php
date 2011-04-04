<?php

namespace DrSlump\Protobuf;

use DrSlump\Protobuf;

class Field
{
    public $number;
    public $name;
    public $nameOrig;
    public $type = Protobuf::TYPE_UNKNOWN;
    public $rule = Protobuf::RULE_OPTIONAL;
    public $reference;
    public $default;
    public $packed = false;
    public $extension = false;

    /*
    public function __construct($tag=null, $opts=array())
    {
        $this->tag = (int)$tag;
        $this->name = $opts['name'];
        $this->type = (int)$opts['type'];
        $this->rule = (int)$opts['rule'];
        $this->packed = isset($opts['packed']) ? (bool)$opts['packed'] : false;
        $this->reference = isset($opts['reference']) ? $opts['reference'] : null;
    }
    */

    public function getNumber()
    {
        return $this->number;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getReference()
    {
        return $this->reference;
    }

    public function getDefault()
    {
        return $this->default;
    }

    public function hasDefault()
    {
        return $this->default !== NULL;
    }

    public function isOptional()
    {
        return $this->rule === Protobuf::RULE_OPTIONAL;
    }

    public function isRequired()
    {
        return $this->rule === Protobuf::RULE_REQUIRED;
    }

    public function isRepeated()
    {
        return $this->rule === Protobuf::RULE_REPEATED;
    }

    public function isPacked()
    {
        return $this->packed;
    }

    public function isExtension()
    {
        return $this->extension;
    }
}