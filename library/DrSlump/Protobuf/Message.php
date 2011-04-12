<?php

namespace DrSlump\Protobuf;

use DrSlump\Protobuf;

abstract class Message implements \ArrayAccess
{
    /** @var \DrSlump\Protobuf\Descriptor */
    static protected $descriptor;

    /** @var \Closure[] */
    static protected $__extensions = array();

    /** @var array Store data for extension fields */
    protected $_extensions = array();
    /** @var array Store data for unknown values */
    protected $_unknown = array();

    /**
     * @static
     * @abstract
     * @param Descriptor|null $descriptor
     * @return \DrSlump\Protobuf\Descriptor
     */
    abstract public static function descriptor(\DrSlump\Protobuf\Descriptor $descriptor = null);

    public static function extension(\Closure $fn)
    {
        static::$__extensions[] = $fn;
    }

    public function __construct($data = null)
    {
        // Assign default values to extensions
        $d = static::descriptor();
        foreach ($d->getFields() as $f) {
           if ($f->isExtension() && $f->hasDefault()) {
               $this->_extensions[$f->getName()] = $f->getDefault();
           }
        }

        if ($data !== NULL) {
            $this->parse($data);
        }
    }

    // Implements ArrayAccess for extensions and unknown fields

    public function offsetExists($offset)
    {
        if (is_numeric($offset)) {
            return $this->_has($offset);
        } else {
            return $this->hasExtension($offset);
        }
    }

    public function offsetSet($offset, $value)
    {
        if (is_numeric($offset)) {
            $this->_set($offset, $value);
        } else {
            $this->setExtension($offset, $value);
        }
    }

    public function offsetGet( $offset )
    {
        if (is_numeric($offset)) {
            return $this->_get($offset);
        } else {
            return $this->getExtension($offset);
        }
    }

    public function offsetUnset( $offset )
    {
        if (is_numeric($offset)) {
            $this->_clear($offset);
        } else {
            $this->clearExtension($offset);
        }
    }


    public function parse($data, Protobuf\CodecInterface $codec = null)
    {
        $codec = Protobuf::getCodec($codec);
        $codec->decode($this, $data);
    }

    public function serialize(Protobuf\CodecInterface $codec = null)
    {
        $codec = Protobuf::getCodec($codec);
        return $codec->encode($this);
    }


    public function _has($tag)
    {
        $d = static::descriptor();

        if ($d->hasField($tag)) {
            $f = $d->getField($tag);
            $name = $f->getName();

            if ($f->isExtension()) {
                return $f->isRepeated()
                       ? count($this->_extensions[$name])
                       : $this->_extensions[$name] !== NULL;
            } else {
                return $f->isRepeated()
                       ? count($this->$name) > 0
                       : $this->$name !== NULL;
            }
        }

        return false;
    }

    public function _get($tag, $idx = null)
    {
        $d = static::descriptor();
        $f = $d->getField($tag);

        if (!$f) {
            return null;
        }

        $name = $f->getName();

        if (!$f->isExtension()) {

            return $idx !== NULL
                   ? $this->$name[$idx]
                   : $this->$name;

        } else {

            return $idx !== NULL
                   ? $this->_extensions[$name][$idx]
                   : $this->_extensions[$name];

        }

    }

    public function _set($tag, $value, $idx = null)
    {
        $d = static::descriptor();
        $f = $d->getField($tag);

        if (!$f) {
            throw new \Exception('Unknown fields not supported');
        }

        $name = $f->getName();
        if (!$f->isExtension()) {

            if ($idx === NULL) {
                $this->$name = $value;
            } else {
                $this->{$name}[$idx] = $value;
            }

        } else {
            if ($idx === NULL) {
                $this->_extensions[$name] = $value;
            } else {
                $this->_extensions[$name][$idx] = $value;
            }
        }

        return $this;
    }

    public function _add($tag, $value)
    {
        $d = static::descriptor();
        $f = $d->getField($tag);

        if (!$f) {
            throw new \Exception('Unknown fields not supported');
        }

        $name = $f->getName();
        if (!$f->isExtension()) {
            $this->{$name}[] = $value;
        } else {
            $this->_extensions[$name][] = $value;
        }

        return $this;
    }

    public function _clear($tag)
    {
        $d = static::descriptor();
        $f = $d->getField($tag);

        if (!$f) {
            throw new \Exception('Unknown fields not supported');
        }

        $name = $f->getName();
        if (!$f->isExtension()) {
            $this->$name = $f->isRepeated()
                           ? array()
                           : NULL;
        } else {
            $this->_extensions[$name] = $f->isRepeated()
                                      ? array()
                                      : NULL;
        }

        return $this;
    }

    // Extensions public methods.
    // @todo Check if extension name is defined

    public function hasExtension($extname)
    {
        return isset($this->_extensions[$extname]);
    }

    public function getExtension($extname, $idx = null)
    {
        if (!isset($this->_extensions[$extname])) return NULL;

        return $idx === NULL
               ? $this->_extensions[$extname]
               : $this->_extensions[$extname][$idx];
    }

    public function getExtensionList($extname)
    {
        return isset($this->_extensions[$extname])
               ? $this->_extensions[$extname]
               : array();
    }

    public function setExtension($extname, $value, $idx = null)
    {
        $this->_extensions[$extname] = $value;
    }

    public function addExtension($extname, $value)
    {
        $this->_extensions[$extname][] = $value;
    }

    public function clearExtension($extname)
    {
        unset($this->_extensions[$extname]);
    }


    public function addUnknown(Unknown $unknown)
    {
        $this->_unknown[] = $unknown;
    }

    public function getUnknown()
    {
        return $this->_unknown;
    }

    public function clearUnknown()
    {
        $this->_unknown = array();
    }

}
