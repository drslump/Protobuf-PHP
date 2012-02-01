<?php

namespace DrSlump\Protobuf;

/**
 *
 * To access fields by their name you can use generated getters/setters methods if
 * available or using magic methods for properties access.
 *
 *    $msg->setFoo( $msg->getBar() );
 *    $msg->foo = $msg->bar;
 *
 * To access fields by its tag number please use the ArrayAccess methods.
 *
 *    $msg[4] = 'Value for field with tag 4';
 *
 * To simplify the use of extended fields, the ArrayAccess methods also support
 * accessing the extension fields by their name.
 *
 *    $msg['company.extfield'] = 'Value for extension field';
 *
 */
interface MessageInterface extends \ArrayAccess
{
    /**
     * @static
     * @abstract
     * @return \DrSlump\Protobuf\Descriptor
     */
    public static function descriptor();

    /**
     * Register an extension configuration callback
     *
     * @static
     * @param \Closure $fn
     */
    public static function extension(\Closure $fn);

    /**
     * @param mixed $data
     */
    public function __construct($data = null);

    /**
     * Parse the given data to hydrate the object
     *
     * @param string $data
     * @param CodecInterface|null $codec
     */
    public function parse($data, Protobuf\CodecInterface $codec = null);

    /**
     * Serialize the current object data
     *
     * @param CodecInterface|null $codec
     * @return string
     */
    public function serialize(Protobuf\CodecInterface $codec = null);

     /**
     * Clears all the data in the message object
     */
    public function reset();

    /**
     * Import an array with fields
     *
     * @param array $array
     */
    public function fromArray($array);

    /**
     * Export the current message data as an assoc array
     *
     * @return array
     */
    public function toArray();


    /**
     * Initializes a field without managing it. Mainly used by codecs.
     */
    public function initValue($name, $value);

    /**
     * Initializes an extension without managing it. Mainly used by codecs.
     */
    public function initExtension($name, $value);



    // Extensions public methods.

    /**
     * Checks if an extension field is set
     *
     * @param string $extname
     * @return bool
     */
    public function hasExtension($extname);

    /**
     * Get the value of an extension field
     *
     * @param string $extname
     * @param int|null $idx
     * @return mixed
     */
    public function getExtension($extname, $idx = null);

    /**
     * Set the value for an extension field
     *
     * @param string $extname
     * @param mixed $value
     * @param int|null $idx
     * @return \DrSlump\Protobuf\Message - Fluent Interface
     */
    public function setExtension($extname, $value, $idx = null);

    /**
     * Adds a value to repeated extension field
     *
     * @param string $extname
     * @param mixed $value
     * @return \DrSlump\Protobuf\Message - Fluent Interface
     */
    public function addExtension($extname, $value);

    /**
     * @param  $extname
     * @return void
     */
    public function clearExtension($extname);


    // Unknown fields

    /**
     * Adds an unknown field to the message
     * 
     * @param \DrSlump\Protobuf\Unknown string $field
     * @return \DrSlump\Protobuf\Message - Fluent Interface
     */
    public function addUnknown(Unknown $field);

    /**
     * Obtain the list of unknown fields in this message
     *
     * @return \DrSlump\Protobuf\Unknown[]
     */
    public function getUnknown();
}

