<?php

namespace DrSlump\Protobuf;

use DrSlump\Protobuf;

class LazyRepeat extends LazyValue implements \Iterator, \Countable, \ArrayAccess 
{
    protected $_ofs = 0;

    /** @var array - Flag array elements already decoded */
    protected $_decoded = array();

    public function __construct($values = array())
    {
        $this->value = $values;
    }

    public function current(){
        return $this[$this->_ofs];
    }

    public function key() {
        return $this->_ofs;
    }
    
    public function next() {
        $this->_ofs++;
    }

    public function rewind() {
        $this->_ofs = 0;
    }
        
    public function valid() {
        return isset($this->value[$this->_ofs]);
    }


    public function count() {
        return count($this->value);
    }


    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->value[] = $value;
            $offset = count($this->value)-1;
        } else {
            $this->value[$offset] = $value;
        }

        $this->_decoded[$offset] = true;
    }

    public function offsetExists($offset) {
        return isset($this->value[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->value[$offset]);
    }

    public function offsetGet($offset) {
        if (!isset($this->value[$offset])) return NULL;

        if (empty($this->_decoded[$offset])) {
            $this->value[$offset] = $this->codec->lazyDecode($this->descriptor, $this->value[$offset]);
            $this->_decoded[$offset] = true;
        }

        return $this->value[$offset];
    }

    /**
     * Decodes the lazy value to obtain a valid result
     *
     * @return \DrSlump\Protobuf\LazyRepeat (self)
     */
    public function __invoke()
    {
        return $this;
    }


    public function toArray() {
        // TODO: We might need to do this recursively for repeated messages
        return iterator_to_array($this);
    }
}


