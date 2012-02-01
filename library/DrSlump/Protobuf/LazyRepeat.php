<?php

namespace DrSlump\Protobuf;


class LazyRepeat extends LazyValue implements \Iterator, \ArrayAccess
{
    protected $_ofs = 0;
    protected $_data = NULL;

    public function current(){
        // TODO: Check if it's a lazy value
        return $this->_data[$this->_ofs];
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
        // This method is called before any other iterator method
        if (NULL === $this->_data) {
            $this->evaluate();
        }

        return isset($this->_data[$this->_ofs]);
    }


    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->_data[] = $value;
        } else {
            $_data[$offset] = $value;
        }
    }

    public function offsetExists($offset) {
        return isset($this->_data[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->_data[$offset]);
    }

    public function offsetGet($offset) {
        return isset($this->_data[$offset]) ? $this->_data[$offset] : null;
    }


    /**
     * Decodes the lazy value to obtain a valid result
     *
     * @return LazyRepeat - the self object but already processed
     */
    public function evaluate()
    {
        // TODO: Convert the lazy value into a valid value 
        return $this;
    } 


    public function toArray() {
        // TODO: We might need to do this recursively for repeated messages
        return iterator_to_array($this);
    }
}


