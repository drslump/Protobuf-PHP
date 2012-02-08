<?php

namespace DrSlump\Protobuf;

class LazyValue 
{
    // Use public properties instead of a constructor since it's way faster
    public $codec = NULL;
    public $descriptor = NULL;
    public $value = NULL;
    
    // Avoid the use of this factory in tight loops since it's much slower than
    // setting the properties directly
    static public function factory($codec, $descriptor, $value)
    {
        $obj = new static();
        $obj->codec = $codec;
        $obj->descriptor = $descriptor;
        $obj->value = $value;
        return $obj;
    }

    /**
     * Decodes the lazy value to obtain a valid result
     *
     * @return mixed
     */
    public function __invoke()
    {
        return $this->codec->lazyDecode($this->descriptor, $this->value);
    }

}
