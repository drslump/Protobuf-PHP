<?php

namespace DrSlump\Protobuf\Compiler;

use google\protobuf as proto;

abstract class AbstractGenerator
{
    /** @var \DrSlump\Protobuf\Compiler; */
    protected $comp;

    /** @var array */
    protected $extensions = array();

    public function __construct(\DrSlump\Protobuf\Compiler $compiler)
    {
        $this->comp = $compiler;
    }

    abstract public function getNamespace(proto\FileDescriptorProto $proto);

    abstract public function compileProtoFile(proto\FileDescriptorProto $proto);

    abstract public function compileEnum(proto\EnumDescriptorProto $enum, $namespace);

    abstract public function compileMessage(proto\DescriptorProto $msg, $namespace);

    abstract public function compileExtension(proto\FieldDescriptorProto $field, $ns, $indent);
}
