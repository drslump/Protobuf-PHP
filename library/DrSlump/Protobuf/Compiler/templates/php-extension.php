<?php /*

    Receives the following variables:

        $namespace - The current namespace (aka package)
        $data      - An array of google.protobuf.FieldDescriptorProto objects

    */ ?>
 
<?php foreach ($data as $f): ?>

\<?php echo $this->ns($f->extendee)?>::extension(function(){
    
    // <?php echo $this->rule($f)?> <?php echo $this->type($f)?> <?php echo $f->name?> = <?php echo $f->number?> 
    $f = new \DrSlump\Protobuf\Field();
    $f->number = <?php echo $f->number?>;
    $f->name   = "<?php echo $f->name?>";
    $f->rule   = \DrSlump\Protobuf::RULE_<?php echo $this->rule($f)?>;
    $f->type   = \DrSlump\Protobuf::TYPE_<?php echo $this->type($f)?>;
    <?php if ($f->hasTypeName()):
        $ref = $f->type_name;
        if (substr($ref, 0, 1) !== '.') {
            throw new \RuntimeException("Only fully qualified names are supported but found '$ref' at $ns");
        }
    ?>
    $f->reference = '\<?php echo $this->ns($f->reference)?>';
    <?php endif ?>
    <?php
    if ($f->hasDefaultValue()):
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

    // @@protoc_insertion_point(scope_extension)
    // @@protoc_insertion_point(extension_<?php echo $namespace?>:<?php echo $f->name?>)
    
    return $f;
});

<?php endforeach ?>
