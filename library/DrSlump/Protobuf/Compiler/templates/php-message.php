<? /*

    Receives the following variables:

        $namespace - The current namespace (aka package) for the file
        $data - A google.protobuf.DescriptorProto object

    */ ?>
namespace <?=$this->ns($namespace)?> {

    // @@protoc_insertion_point(scope_namespace)
    // @@protoc_insertion_point(namespace_<?=$namespace?>)

    <?
    // Compute a new namespace with the message name as suffix
    $ns = $namespace . '.' . $data->name;
    ?>
    <? if ($this->comment($ns)): ?>
    /**
     * <?=$this->comment($ns, '     * ')?> 
     */
    <? endif ?> 
    class <?=$data->name?> extends \DrSlump\Protobuf\Message {
        <? if (!empty($data->field)): foreach ($data->field as $field): ?>
        <? // Nothing to do ?>
        <? endforeach; endif; ?>
     
        /** @var \DrSlump\Protobuf\Descriptor */
        protected static $__descriptor;
        /** @var \Closure[] */
        protected static $__extensions = array();

        public static function descriptor()
        {
            $descriptor = new \DrSlump\Protobuf\Descriptor(__CLASS__, '<?=$ns?>');

            <? if (!empty($data->field)): foreach ($data->field as $f): ?> 
            // <?=$this->rule($f)?> <?=$this->type($f)?> <?=$f->name?> = <?=$f->number?> 
            $f = new \DrSlump\Protobuf\Field();
            $f->number = <?=$f->number?>;
            $f->name   = "<?=$this->fieldname($f)?>";
            $f->rule   = \DrSlump\Protobuf::RULE_<?=strtoupper($this->rule($f))?>;
            $f->type   = \DrSlump\Protobuf::TYPE_<?=strtoupper($this->type($f))?>;
            <? if (!empty($f->type_name)):
                $ref = $f->type_name;
                if (substr($ref, 0, 1) !== '.') {
                    throw new \RuntimeException("Only fully qualified names are supported but found '$ref' at $ns");
                }
            ?> 
            $f->reference = '\<?=$this->ns($ref)?>';
            <? endif ?>

            <?
            if (isset($f->default_value)):
                switch ($f->type) {
                case \DrSlump\Protobuf::TYPE_BOOL:
                    $bool = filter_var($f->default_value, FILTER_VALIDATE_BOOLEAN);
            ?> 
            $f->default = <?=$bool ? 'true' : 'false'?>;
            <?
                break;
                case \DrSlump\Protobuf::TYPE_STRING:
            ?> 
            $f->default = '<?=addcslashes($f->default_value, "'\\")?>';
            <?
                break;
                case \DrSlump\Protobuf::TYPE_ENUM:
            ?> 
            $f->default = \<?=$this->ns($f->type_name)?>::<?=$f->default_value?>;
            <?
                break;
                default: // Numbers
            ?> 
            $f->default = <?=$f->default_value?>;
            <?
                } // switch
            endif;
            ?>

            // @@protoc_insertion_point(scope_field)
            // @@protoc_insertion_point(field_<?=$ns?>:<?=$f->name?>)

            $descriptor->addField($f);
            <? endforeach; endif; ?>

            foreach (self::$__extensions as $cb) {
                $descriptor->addField($cb(), true);
            }

            // @@protoc_insertion_point(scope_descriptor)';
            // @@protoc_insertion_point(descriptor_<?=$ns?>)

            return $descriptor;
        }


        <? if (!empty($data->field)): foreach ($data->getField() as $f): ?>
        <?
            $name = $this->fieldname($f);
            $Name = $this->camelize(ucfirst($name));
        ?>

        /**
         * Check if "<?=$name?>" has a value
         *
         * @return boolean
         */
        public function has<?=$Name?>()
        {
            return isset($this-><?=$name?>);
        }

        /**
         * Clear "<?=$name?>" value
         */
        public function clear<?=$Name?>()
        {
            unset($this-><?=$name?>);
        }

        <? if ($f->label === \DrSlump\Protobuf::RULE_REPEATED): ?>

        /**
         * Get "<?=$name?>" value
         *
         * @return <?=$this->doctype($f)?>[]
         */
        public function get<?=$Name?>($idx = null)
        {
            if (NULL !== $idx) {
                return $this-><?=$name?>[$idx];
            }

            return $this-><?=$name?>;
        }

        /**
         * Get "<?=$name?>" list of values
         *
         * @return <?=$this->doctype($f)?>[]
         */
        public function get<?=$Name?>List()
        {
            return $this->get<?=$Name?>();
        }

        /**
         * Set "<?=$name?>" value
         *
         * @param <?=$this->doctype($f)?>[] $value
         */
        public function set<?=$Name?>($value)
        {
            return $this-><?=$name?> = $value;
        }

        /**
         * Add a new element to "<?=$name?>"
         *
         * @param <?=$this->doctype($f)?> $value
         */
        public function add<?=$Name?>($value)
        {
            $this-><?=$name?>[] = $value;
        }

        <? else: ?>

        /**
         * Get "<?=$name?>" value
         *
         * @return <?=$this->doctype($f)?> 
         */
        public function get<?=$Name?>()
        {
            return $this-><?=$name?>;
        }

        /**
         * Set "<?=$name?>" value
         *
         * @param <?=$this->doctype($f)?> $value
         */
        public function set<?=$Name?>($value)
        {
            return $this-><?=$f->name?> = $value;
        }

        <? endif ?>

        <? endforeach; endif; ?>

        // @@protoc_insertion_point(scope_class)
        // @@protoc_insertion_point(class_<?=$ns?>)
    }
}

