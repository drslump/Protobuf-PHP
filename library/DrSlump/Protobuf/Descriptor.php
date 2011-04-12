<?php

namespace DrSlump\Protobuf;

use DrSlump\Protobuf;

class Descriptor
{
    /** @var String Holds the class name of the message */
    protected $message;

    /** @var \DrSlump\Protobuf\Field[] */
    protected $fields = array();


    /**
     * @param string $message
     */
    public function __construct($message)
    {
        $this->message = $message;
    }

    /**
     * @return \DrSlump\Protobuf\Field[]
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param \DrSlump\Protobuf\Field $field
     * @param bool $isExtension
     */
    public function addField(Protobuf\Field $field, $isExtension = false)
    {
        $field->extension = $isExtension;
        $this->fields[ $field->number ] = $field;
    }

    /**
     * @param int $tag
     * @return \DrSlump\Protobuf\Field | NULL
     */
    public function getField($tag)
    {
        return isset($this->fields[$tag])
               ? $this->fields[$tag]
               : NULL;
    }

    /**
     * @param int $tag
     * @return bool
     */
    public function hasField($tag)
    {
        return isset($this->fields[$tag]);
    }
}
