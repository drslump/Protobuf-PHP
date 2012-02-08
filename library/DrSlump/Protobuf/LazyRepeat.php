<?php

namespace DrSlump\Protobuf;

use DrSlump\Protobuf;

// TODO: Inherit from LazyValue so we only have to define the codec and descriptor once,
//       being the value the array of repeated field values. No need to prepopulate the
//       array with LazyValue.
class LazyRepeat implements \Iterator, \Countable, \ArrayAccess 
{
    protected $_ofs = 0;
    protected $_data = array();

    public function __construct($values = array())
    {
        $this->_data = $values;
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
        return isset($this->_data[$this->_ofs]);
    }


    public function count() {
        return count($this->_data);
    }


    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->_data[] = $value;
        } else {
            $this->_data[$offset] = $value;
        }
    }

    public function offsetExists($offset) {
        return isset($this->_data[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->_data[$offset]);
    }

    public function offsetGet($offset) {
        if (!isset($this->_data[$offset])) return NULL;

        if ($this->_data[$offset] instanceof Protobuf\LazyValue) {
            $this->_data[$offset] = $this->_data[$offset]->evaluate();
        }
        return $this->_data[$offset];
    }

    public function toArray() {
        // TODO: We might need to do this recursively for repeated messages
        return iterator_to_array($this);
    }
}


