<?php

namespace DrSlump\Protobuf;

use DrSlump\Protobuf;

class Descriptor
{
    /** @var \DrSlump\Protobuf\Field[] */
    protected $fields = array();

    public function __construct($message)
    {
    }

    /**
     * @return \DrSlump\Protobuf\Field[]
     */
    public function getFields()
    {
        return $this->fields;
    }

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

    public function hasField($tag)
    {
        return isset($this->fields[$tag]);
    }
}
