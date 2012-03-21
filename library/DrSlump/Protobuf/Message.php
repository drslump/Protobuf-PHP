<?php

namespace DrSlump\Protobuf;

use DrSlump\Protobuf;

/**
 *
 * Public fields are generated as PhpDoc properties
 *
 * @property string $string 
 */ 
class Message implements MessageInterface
{
    /** @var \Closure[] */
    static protected $__extensions = array();
    /** @var \DrSlump\Protobuf\Descriptor */
    static protected $__descriptor;

    /** @var \DrSlump\Protobuf\Descriptor */
    protected $_descriptor;
    /** @var array Store data for message fields */
    protected $_values = array();
    /** @var array Store data for extension fields */
    protected $_extensions = array();
    /** @var array Store data for unknown values */
    protected $_unknown = array();


    /**
     * @static
     * @abstract
     * @return \DrSlump\Protobuf\Descriptor
     */
    public static function descriptor()
    {
        throw new \BadMethodCallException('This method should be implemented in inherited classes');
    }

    /**
     * Register an extension configuration callback
     *
     * @static
     * @param \Closure $fn
     */
    public static function extension(\Closure $fn)
    {
        static::$__extensions[] = $fn;
    }


    /**
     * @param mixed $data
     */
    public function __construct($data = null)
    {
        // Cache the descriptor instance for this class
        if (!static::$__descriptor) {
            static::$__descriptor = Protobuf::getRegistry()->getDescriptor($this);
        }

        // Alias the descriptor to this object instance since it's faster to access
        $this->_descriptor = static::$__descriptor;

        // BACKWARDS COMPATIBILITY: Unset public properties
        $publicfields = get_object_vars($this);
        foreach ($this->_descriptor->getFields() as $field) {
            $name = $field->name;
            if (array_key_exists($name, $publicfields)) {
                //trigger_error('DESTROYING PUBLIC FIELD: '  . $name);
                unset($this->$name);
            }
        }

        if (NULL !== $data) {
            $this->parse($data);
        }
    }

    /**
     * Parse the given data to hydrate the object
     *
     * @param string $data
     * @param CodecInterface|null $codec
     */
    public function parse($data, Protobuf\CodecInterface $codec = null)
    {
        $codec = Protobuf::getCodec($codec);
        $codec->decode($this, $data);
    }

    /**
     * Serialize the current object data
     *
     * @param CodecInterface|null $codec
     * @return string
     */
    public function serialize(Protobuf\CodecInterface $codec = null)
    {
        $codec = Protobuf::getCodec($codec);
        return $codec->encode($this);
    }

    /**
     * Initializes a field without managing it
     */
    public function initValue($name, $value)
    {
        $this->_values[$name] = $value;
    }

    /**
     * Initializes a field without managing it
     */
    public function initValues($values)
    {
        foreach($values as $k=>$v) {
            $this->_iniValue($k, $v);
        }
    }

    /**
     * Initializes an extension without managing it
     */
    public function initExtension($name, $value)
    {
        $this->_extensions[$name] = $value;
    }

    /**
     * Clears all the data in the message object
     */
    public function reset()
    {
        $this->_values = array();
        $this->_extensions = array();
        $this->_unknown = array();
    }

    /**
     * Import an array with fields
     *
     * @param array $array
     */
    public function fromArray($data) {
        foreach ($data as $k=>$v) {
            $this->$k = $v;
        }
    }

    /**
     * Export the current message data as an assoc array
     *
     * @return array
     */
    public function toArray() {
        $result = array();
        foreach ($this->_values as $k=>$v) {
            // Use the magic getter to obtain a valid value
            $result[$k] = $this->$k;
            if ($result[$k] instanceof MessageInterface) {
                $result[$k] = $result[$k]->toArray();
            } else if ($result[$k] instanceof LazyRepeat) {
                $result[$k] = $result[$k]->toArray();
            }
        }
        return $result;
    }


    // Extensions public methods.
    // @todo Check if extension name is defined

    /**
     * Checks if an extension field is set
     *
     * @param string $extname
     * @return bool
     */
    public function hasExtension($extname)
    {
        return isset($this->_extensions[$extname]);
    }

    /**
     * Get the value of an extension field
     *
     * @param string $extname
     * @param int|null $idx
     * @return mixed
     */
    public function getExtension($extname, $idx = null)
    {
        if (!isset($this->_extensions[$extname])) return NULL;

        return $idx === NULL
               ? $this->_extensions[$extname]
               : $this->_extensions[$extname][$idx];
    }

    /**
     * Get all the values of a repeated extension field
     *
     * @param string $extname
     * @return array
     */
    public function getExtensionList($extname)
    {
        return isset($this->_extensions[$extname])
               ? $this->_extensions[$extname]
               : array();
    }

    /**
     * Set the value for an extension field
     *
     * @param string $extname
     * @param mixed $value
     * @param int|null $idx
     * @return \DrSlump\Protobuf\Message - Fluent Interface
     */
    public function setExtension($extname, $value, $idx = null)
    {
        if (NULL !== $idx) {
            if (empty($this->_extensions)) {
                $this->_extensions[$extname] = array();
            }
            $this->_extensions[$extname][$idx] = $value;
        }

        $this->_extensions[$extname] = $value;

        return $this;
    }

    /**
     * Adds a value to repeated extension field
     *
     * @param string $extname
     * @param mixed $value
     * @return \DrSlump\Protobuf\Message - Fluent Interface
     */
    public function addExtension($extname, $value)
    {
        $this->_extensions[$extname][] = $value;
    }

    /**
     * @param  $extname
     * @return void
     */
    public function clearExtension($extname)
    {
        unset($this->_extensions[$extname]);
    }



    // Unknown fields

    /**
     * Adds an unknown field to the message
     * 
     * @param \DrSlump\Protobuf\Unknown string $field
     * @return \DrSlump\Protobuf\Message - Fluent Interface
     */
    public function addUnknown(Protobuf\Unknown $field)
    {
        $this->_unknown[] = $field;
    }

    /**
     * Obtain the list of unknown fields in this message
     *
     * @return \DrSlump\Protobuf\Unknown[]
     */
    public function getUnknown()
    {
        return $this->_unknown;
    }

    public function clearUnknown()
    {
        $this->_unknown = array();
    }



    // Magic getters and setters

    function &__get($name) 
    {
        if (isset($this->_values[$name])) {
            $value = $this->_values[$name];
            if ($value instanceof Protobuf\LazyValue) {
                $this->_values[$name] = $value();
            }

            return $this->_values[$name];
        }

        $field = $this->_descriptor->getFieldByName($name);
        if (!$field) {
            trigger_error("Protobuf message " . $this->_descriptor->getName() . " doesn't have any field named '$name'.", E_USER_NOTICE);
            return null;
        }

        if ($field && $field->hasDefault()) {
            $this->_values[$name] = $field->getDefault();
        } else if ($field && $field->isRepeated()) {
            $this->_values[$name] = array();
        }

        return $this->_values[$name];
    }

    function __set($name, $value) 
    {
        $this->_values[$name] = $value;
    }

    function __isset($name) 
    {
        if (array_key_exists($name, $this->_values)) {
            return isset($this->_values[$name]);
        }
        
        $field = $this->_descriptor->getFieldByName($name);
        if ($field && $field->hasDefault()) {
            return $field->getDefault() !== NULL;
        }

        return false;
    }

    function __unset($name) 
    {
        unset($this->_values[$name]);
    }

    function __call($name, $args) 
    {
        $prefix = strtolower(substr($name, 0, 3));

        // Check if it's a call we care about
        switch ($prefix) {
        case 'get':
        case 'set':
        case 'has':
        case 'add':
            $name = substr($name, 3);
            break;
        case 'cle':
            if ('ar' === substr($lower, 3, 2)) {
                $name = substr($name, 5);
                break;
            }
        default: 
            throw new \BadMethodCallException('Unknown method "' . $name . '"');
        }

        // Convert from camel-case to underscore
        $normalized = preg_replace('/([a-z])([A-Z0-9])/', '$1_$2', $name);
        $normalized = strtolower($normalized);

        // Do the action
        switch ($prefix) {
        case 'get':
            // Support for getXxxxList() style calls for repeated fields
            if ('_list' === substr($name, -5)) {
                $args = array();
            }

            return count($args) ? $this->$normalized[$args[0]] : $this->$normalized;
        case 'set':
            $this->$normalized = $args[0];
            break;
        case 'has':
            return isset($this->$normalized);
            break;
        case 'add':
            $this->{$normalized}[] = $args[0];
            break;
        case 'cle':
            unset($this->$normalized);
            break;
        }
    }


    // Implements ArrayAccess for tag numbers and extensions

    public function offsetExists($offset)
    {
        if (is_numeric($offset)) {
            $field = $this->_descriptor->getField($offset);
            if (!$field) return NULL;
            if (!$field->extension) {
                return isset($this->_values[$field->name]);
            }
        }
        
        return $this->hasExtension($offset);
    }

    public function offsetSet($offset, $value)
    {
        if (is_numeric($offset)) {
            $field = $this->_descriptor->getField($offset);
            if (!$field) {
                trigger_error("Protobuf message " . $this->_descriptor->getName() . " doesn't have any field with a tag number of $offset", E_USER_NOTICE);
                return;
            }

            if ($field->isExtension()) {
                $data =& $this->_extensions;
            } else {
                $data =& $this->_values;
            }

            $data[$field->name] = $value;
        } else {
            $this->setExtension($offset, $value);
        }
    }

    public function offsetGet( $offset )
    {
        if (is_numeric($offset)) {
            $field = $this->_descriptor->getField($offset);
            if (!$field) {
                trigger_error("Protobuf message " . $this->_descriptor->getName() . " doesn't have any field with a tag number of $offset", E_USER_NOTICE);
                return null;
            }

            $name = $field->name;

            if (!$field->isExtension()) {
                return isset($this->$name) ? $this->$name : NULL;
            }

            return isset($this->_extensions[$name]) ? $this->_extensions[$name] : NULL;
        } else {
            return $this->getExtension($offset);
        }
    }

    public function offsetUnset( $offset )
    {
        if (is_numeric($offset)) {
            $field = $this->_descriptor->getField($offset);            
            if (!$field) {
                trigger_error("Protobuf message " . $this->_descriptor->getName() . " doesn't have any field with a tag number of $offset", E_USER_NOTICE);
                return;
            }

            if ($field->isExtension()) {
                $data =& $this->_extensions;
            } else {
                $data =& $this->_values;
            }

            if (isset($data[$field->name])) {
                unset($data[$field->name]);
            }
        } else {
            $this->clearExtension($offset);
        }
    }

}

