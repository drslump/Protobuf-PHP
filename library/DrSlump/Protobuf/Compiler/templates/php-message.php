<?php /*

    Receives the following variables:

        $namespace - The current namespace (aka package) for the file
        $data - A google.protobuf.DescriptorProto object

    */ ?>
namespace <?php echo $this->ns($namespace)?> {

    // @@protoc_insertion_point(scope_namespace)
    // @@protoc_insertion_point(namespace_<?php echo $namespace?>)

    <?php
    // Compute a new namespace with the message name as suffix
    $ns = $namespace . '.' . $data->name;
    ?>
    <?php if ($this->comment($ns)): ?>
    /**
     * <?php echo $this->comment($ns, '     * ')?> 
     */
    <?php endif ?> 
    class <?php echo $data->name?> extends \DrSlump\Protobuf\Message {
        <?php if (!empty($data->field)): foreach ($data->field as $field): ?>
        <?php // Nothing to do ?>
        <?php endforeach; endif; ?>
     
        /** @var \DrSlump\Protobuf\Descriptor */
        protected static $__descriptor;
        /** @var \Closure[] */
        protected static $__extensions = array();

        public static function descriptor()
        {
            $descriptor = new \DrSlump\Protobuf\Descriptor(__CLASS__, '<?php echo $ns?>');

            <?php if (!empty($data->field)): foreach ($data->field as $f): ?> 
            // <?php echo $this->rule($f)?> <?php echo $this->type($f)?> <?php echo $f->name?> = <?php echo $f->number?> 
            $f = new \DrSlump\Protobuf\Field();
            $f->number = <?php echo $f->number?>;
            $f->name   = "<?php echo $this->fieldname($f)?>";
            $f->rule   = \DrSlump\Protobuf::RULE_<?php echo strtoupper($this->rule($f))?>;
            $f->type   = \DrSlump\Protobuf::TYPE_<?php echo strtoupper($this->type($f))?>;
            <?php if (!empty($f->type_name)):
                $ref = $f->type_name;
                if (substr($ref, 0, 1) !== '.') {
                    throw new \RuntimeException("Only fully qualified names are supported but found '$ref' at $ns");
                }
            ?> 
            $f->reference = '\<?php echo $this->ns($ref)?>';
            <?php endif ?>

            <?php
            if (isset($f->default_value)):
                switch ($f->type) {
                case \DrSlump\Protobuf::TYPE_BOOL:
                    $bool = filter_var($f->default_value, FILTER_VALIDATE_BOOLEAN);
            ?> 
            $f->default = <?php echo $bool ? 'true' : 'false'?>;
            <?php
                break;
                case \DrSlump\Protobuf::TYPE_STRING:
            ?> 
            $f->default = '<?php echo addcslashes($f->default_value, "'\\")?>';
            <?php
                break;
                case \DrSlump\Protobuf::TYPE_ENUM:
            ?> 
            $f->default = \<?php echo $this->ns($f->type_name)?>::<?php echo $f->default_value?>;
            <?php
                break;
                default: // Numbers
            ?> 
            $f->default = <?php echo $f->default_value?>;
            <?php
                } // switch
            endif;
            ?>

            // @@protoc_insertion_point(scope_field)
            // @@protoc_insertion_point(field_<?php echo $ns?>:<?php echo $f->name?>)

            $descriptor->addField($f);
            <?php endforeach; endif; ?>

            foreach (self::$__extensions as $cb) {
                $descriptor->addField($cb(), true);
            }

            // @@protoc_insertion_point(scope_descriptor)';
            // @@protoc_insertion_point(descriptor_<?php echo $ns?>)

            return $descriptor;
        }


        <?php if (!empty($data->field)): foreach ($data->getField() as $f): ?>
        <?php
            $name = $this->fieldname($f);
            $Name = $this->camelize(ucfirst($name));
        ?>

        /**
         * Check if "<?php echo $name?>" has a value
         *
         * @return boolean
         */
        public function has<?php echo $Name?>()
        {
            return isset($this-><?php echo $name?>);
        }

        /**
         * Clear "<?php echo $name?>" value
         */
        public function clear<?php echo $Name?>()
        {
            unset($this-><?php echo $name?>);
        }

        <?php if ($f->label === \DrSlump\Protobuf::RULE_REPEATED): ?>

        /**
         * Get "<?php echo $name?>" value
         *
         * @return <?php echo $this->doctype($f)?>[]
         */
        public function get<?php echo $Name?>($idx = null)
        {
            if (NULL !== $idx) {
                return $this-><?php echo $name?>[$idx];
            }

            return $this-><?php echo $name?>;
        }

        /**
         * Get "<?php echo $name?>" list of values
         *
         * @return <?php echo $this->doctype($f)?>[]
         */
        public function get<?php echo $Name?>List()
        {
            return $this->get<?php echo $Name?>();
        }

        /**
         * Set "<?php echo $name?>" value
         *
         * @param <?php echo $this->doctype($f)?>[] $value
         */
        public function set<?php echo $Name?>($value)
        {
            return $this-><?php echo $name?> = $value;
        }

        /**
         * Add a new element to "<?php echo $name?>"
         *
         * @param <?php echo $this->doctype($f)?> $value
         */
        public function add<?php echo $Name?>($value)
        {
            $this-><?php echo $name?>[] = $value;
        }

        <?php else: ?>

        /**
         * Get "<?php echo $name?>" value
         *
         * @return <?php echo $this->doctype($f)?> 
         */
        public function get<?php echo $Name?>()
        {
            return $this-><?php echo $name?>;
        }

        /**
         * Set "<?php echo $name?>" value
         *
         * @param <?php echo $this->doctype($f)?> $value
         */
        public function set<?php echo $Name?>($value)
        {
            return $this-><?php echo $f->name?> = $value;
        }

        <?php endif ?>

        <?php endforeach; endif; ?>

        // @@protoc_insertion_point(scope_class)
        // @@protoc_insertion_point(class_<?php echo $ns?>)
    }
}

