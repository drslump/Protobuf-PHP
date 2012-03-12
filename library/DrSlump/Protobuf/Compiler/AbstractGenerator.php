<?php

namespace DrSlump\Protobuf\Compiler;

use DrSlump\Protobuf;
use google\protobuf as proto;

abstract class AbstractGenerator
{
    /** @var \DrSlump\Protobuf\Compiler */
    protected $compiler;

    /** @var \google\protobuf\FileDescriptorProto */
    protected $proto;

    /** @var array - Keeps a list of extension definitions found */
    protected $extensions = array();

    /** @var string Character used as namespace separator */
    protected $nsSep = '\\';

    /** @var string Prefix used for custom options */
    protected $prefix = 'php';

    /** @var array List of reserved keywords. Field names colliding are suffixed with '_' */
    protected $reserved = array();


    public function __construct(\DrSlump\Protobuf\Compiler $compiler)
    {
        $this->compiler = $compiler;
    }


    /**
     * Implement this method in your custom generator
     *
     * @param \google\protobuf\FileDescriptorProto $proto
     * @return \google\protobuf\compiler\CodeGeneratorResponse\File[]
     */
    abstract public function generate(proto\FileDescriptorProto $proto);

    /**
     * Obtain the target namespace for the given proto. The compiler frontend calls
     * this method to build a map of packages to target namespaces.
     * 
     * @param \google\protobuf\FileDescriptorProto $proto
     * @return string
     */
    public function getNamespace(proto\FileDescriptorProto $proto)
    {
        $copts = $this->compiler->options;
        $popts = $proto->options;

        if (isset($copts['namespace'])) {
            $namespace = $copts['namespace'];
        } else if (isset($copts['package'])) {
            $namespace = $copts['package'];
        } else if (!empty($popts) && isset($popts[$this->prefix . '.namespace'])) {
            $namespace = $popts[$this->prefix . '.namespace'];
        } else if (!empty($popts) && isset($popts[$this->prefix . '.package'])) {
            $namespace = $popts[$this->prefix . '.package'];
        } else {
            $namespace = $proto->package;
        }

        $namespace = trim(trim($namespace, '.'), $this->nsSep);
        return str_replace('.', $this->nsSep, $namespace);
    }

    /**
     * Set a new namespace separator character for this generator
     *
     * @param string $sep
     */ 
    public function setNamespaceSeparator($sep)
    {
        $this->nsSep = $sep;
    }

    /**
     * Get the current namespace separator char
     *
     * @return string
     */
    public function getNamespaceSeparator()
    {
        return $this->nsSep;
    }

    /**
     * Set the configured prefix
     *
     * @param string $prefix
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * Obtain the currently configured prefix
     * 
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * Obtain a normalized field name taking into account reserved keywords
     *
     * @param \google\protobuf\FieldDescriptorProto $field
     * @return string
     */
    public function fieldname(\google\protobuf\FieldDescriptorProto $field)
    {
        if (in_array($field->name, $this->reserved)) {
            return $field->name . '_';
        }
        return $field->name;
    }

    /**
     * Get an option from the compiler arguments or from the proto file.
     *
     * @param string $name
     * @return string|null
     */
    public function option($name, $default = NULL)
    {
        $opt = NULL;

        // Check the compiler frontend 
        if (isset($this->compiler->options[$name])) {
            $opt = $this->compiler->options[$name];
        // otherwise check the current proto file
        } else if (!empty($this->proto->options)) {
            $opts = $this->proto->options;
            $name = $this->prefix . '.' . $name; 
            if (isset($opts[$this->prefix . '.' . $name])) {
                $opt = $opts[$this->prefix . '.' . $name];
            } else if (isset($opts[$name])) {
                $opt = $opts[$name];
            }
        }

        if (NULL === $opt) {
            return $default;
        }

        // If it looks like a boolean value cast it
        if (preg_match('/^(on|off|true|false|yes|no)$/i', $opt)) {
            return filter_var($opt, FILTER_VALIDATE_BOOLEAN);
        }

        return $opt;
    }

    /**
     * Convert a Protobuf package to a target language one 
     *
     * @param string $ns
     * @return string
     */
    public function ns($ns)
    {
        // Alias the packages registry to a local var
        $map =& $this->compiler->packages;

        // Remove leading dot (used in references)
        $package = ltrim($ns, '.');

        if (empty($package)) {
            return '';
        }

        if (isset($map[$package])) {
            return $map[$package];
        }

        // Check the currently registered packages to find a root one
        $found = null;
        foreach ($map as $pkg=>$ns) {
            // Keep only the longest match
            if (0 === strpos($package, $pkg.'.') && strlen($found) < strlen($pkg)) {
                $found = $pkg;
            }
        }

        // If no matching package was found issue a warning and use the package name
        if (!$found) {
            $this->compiler->warning('Non tracked package name found "' . $package . '" ');
            $namespace = str_replace('.', $this->nsSep, $package);
        } else {
            // Complete the namespace with the remaining package
            $namespace = $map[$found];
            $namespace .= substr($package, strlen($found));
            $namespace = str_replace('.', $this->nsSep, $namespace);

            // Set the newly found namespace in the registry
            $map[$package] = $namespace;
        }

        return $namespace;
    }


    /**
     * Obtain the comment doc associated to an identifier
     *
     * @param string $ident
     * @param string $prefix
     * @return string
     */
    public function comment($ident, $prefix = '')
    {

        return $this->compiler->getComment($ident, $prefix);
    }

    /**
     * Converts to CamelCase an string
     *
     * @param string $str
     * @return string
     */
    public function camelize($str)
    {
        return preg_replace_callback(
                    '/_([a-z0-9])/i',
                    function($m){ return strtoupper($m[1]); },
                    $str
                 );
    }

    /**
     * Obtain the rule for the given field (repeated, optional, required)
     *
     * @param proto\FieldDescriptorProto $field
     * return string
     */
    public function rule(proto\FieldDescriptorProto $field)
    {
        switch ($field->label) {
        case Protobuf::RULE_OPTIONAL: return 'optional';
        case Protobuf::RULE_REQUIRED: return 'required';
        case Protobuf::RULE_REPEATED: return 'repeated';
        default: return '*unknown*';
        }
    }

    /**
     * Obtain the type for the given field (int32, string, float, etc.)
     *
     * @param proto\FieldDescriptorProto $field
     * return string
     */
    public function type(proto\FieldDescriptorProto $field)
    {
        switch ($field->label) {
        case Protobuf::TYPE_DOUBLE: return 'double';
        case Protobuf::TYPE_FLOAT: return 'float';
        case Protobuf::TYPE_INT64: return 'int64';
        case Protobuf::TYPE_UINT64: return 'uint64';
        case Protobuf::TYPE_INT32: return 'int32';
        case Protobuf::TYPE_FIXED64: return 'fixed64';
        case Protobuf::TYPE_FIXED32: return 'fixed32';
        case Protobuf::TYPE_BOOL: return 'bool';
        case Protobuf::TYPE_STRING: return 'string';
        case Protobuf::TYPE_MESSAGE: return 'message';
        case Protobuf::TYPE_BYTES: return 'bytes';
        case Protobuf::TYPE_UINT32: return 'uint32';
        case Protobuf::TYPE_ENUM: return 'enum';
        case Protobuf::TYPE_SFIXED32: return 'sfixed32';
        case Protobuf::TYPE_SFIXED64: return 'sfixed64';
        case Protobuf::TYPE_SINT32: return 'sint32';
        case Protobuf::TYPE_SINT64: return 'sint64';
        default: return '*unknown*';
        }
    }

    /**
     * Obtain a JavaDoc style type for the given field (int, float, string)
     *
     * @param proto\FieldDescriptorProto $field
     * return string
     */
    protected function doctype(proto\FieldDescriptorProto $field)
    {
        switch ($field->getType()) {
        case Protobuf::TYPE_DOUBLE:
        case Protobuf::TYPE_FLOAT:
            return 'float';
        case Protobuf::TYPE_INT64:
        case Protobuf::TYPE_UINT64:
        case Protobuf::TYPE_INT32:
        case Protobuf::TYPE_FIXED64:
        case Protobuf::TYPE_FIXED32:
        case Protobuf::TYPE_UINT32:
        case Protobuf::TYPE_SFIXED32:
        case Protobuf::TYPE_SFIXED64:
        case Protobuf::TYPE_SINT32:
        case Protobuf::TYPE_SINT64:
            return 'int';
        case Protobuf::TYPE_BOOL:
            return 'boolean';
        case Protobuf::TYPE_STRING:
            return 'string';
        case Protobuf::TYPE_MESSAGE:
            return $this->ns($field->type_name);
        case Protobuf::TYPE_BYTES:
            return 'string';
        case Protobuf::TYPE_ENUM:
            return 'int - ' . $this->ns($field->type_name);
        default:
            return '*unknown*';
        }
    }
}
