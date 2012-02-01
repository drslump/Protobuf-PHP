<?php

namespace DrSlump\Protobuf;

/**
 *
 * Public fields are generated as PhpDoc properties
 *
 * @property string $string 
 */ 
class LazyMessage implements MessageInterface
{
    /** @var \Closure[] */
    static protected $__extensions = array();

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
        // Cache the descriptor instance
        $this->_descriptor = Protobuf::getRegistry()->getDescriptor($this);

        // Assign default values to extensions
        // TODO: Can we optimize this by having a hasExtensions in the descriptor?
        foreach ($this->_descriptor->getFields() as $f) {
           if ($f->isExtension() && $f->hasDefault()) {
               $this->_extensions[$f->getName()] = $f->getDefault();
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
        foreach (array_keys($this->_values) as $k) {
            $result[$k] = $this->$k;
            if ($result[$k] instanceof Message) {
                $result[$k] = $result[k]->toArray();
            } else if ($result[$k] instanceof LazyRepeat) {
                $result[$k] = $result[k]->toArray();
            }
        }
        return $result;
    }


    // Magic getters and setters

    function __get($name) 
    {
        if (isset($this->_values[$name])) {
            $value = $this->_values[$name];
            if ($value instanceof LazyValue) {
                $this->_values[$name] = $value->evaluate();
            }

            return $this->_values[$name];
        }

        return null;
    }

    function __set($name, $value) 
    {
        $this->_values[$name] = $value;
    }

    function __isset($name) 
    {
        return isset($this->_values[$name]);
    }

    function __unset($name) 
    {
        unset($_values[$name]);
    }

    function __call($name, $args) 
    {
        $prefix = strtolower(substr($lower, 0, 3));

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
        $normalized = preg_replace('/([a-z])([A-Z])/', '$1_$2', $name);
        $normalized = strtolower($normalized);

        // Do the action
        switch ($prefix) {
        case 'get':
            return $this->$normalized;
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
            return $this->_descriptor->getField($offset) !== NULL;
        } else {
            return $this->hasExtension($offset);
        }
    }

    public function offsetSet($offset, $value)
    {
        if (is_numeric($offset)) {
            $field = $this->_descriptor->getField($offset);
            if (!$field) {
                throw new \Exception('Invalid field tag number ' . $offset);
            }
            $data =& $field->isExtension() ? $this->_extensions : $this->_values;
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
                throw new \Exception('Invalid field tag number ' . $offset);
            }
            $data =& $field->isExtension() ? $this->_extensions : $this->_values;
            return isset($data[$field->name]) ? $data[$field->name] : NULL;
        } else {
            return $this->getExtension($offset);
        }
    }

    public function offsetUnset( $offset )
    {
        if (is_numeric($offset)) {
            $field = $this->_descriptor->getField($offset);
            if (!$field) {
                throw new \Exception('Invalid field tag number ' . $offset);
            }
            $data =& $field->isExtension() ? $this->_extensions : $this->_values;
            if (isset($data[$field->name])) {
                unset($data[$field->name]);
            }
        } else {
            $this->clearExtension($offset);
        }
    }
}

