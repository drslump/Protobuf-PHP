<? /*

    Receives the following variables:

        $namespace - The current namespace (aka package)
        $data      - An array of google.protobuf.FieldDescriptorProto objects

    */ ?>
 
<? foreach ($data as $f): ?>

\<?=$this->ns($f->extendee)?>::extension(function(){
    
    // <?=$this->rule($f)?> <?=$this->type($f)?> <?=$f->name?> = <?=$f->number?> 
    $f = new \DrSlump\Protobuf\Field();
    $f->number = <?=$f->number?>;
    $f->name   = "<?=$f->name?>";
    $f->rule   = \DrSlump\Protobuf::RULE_<?=$this->rule($f)?>;
    $f->type   = \DrSlump\Protobuf::TYPE_<?=$this->type($f)?>;
    <? if ($f->hasTypeName()):
        $ref = $f->type_name;
        if (substr($ref, 0, 1) !== '.') {
            throw new \RuntimeException("Only fully qualified names are supported but found '$ref' at $ns");
        }
    ?>
    $f->reference = '\<?=$this->ns($f->reference)?>';
    <? endif ?>
    <?
    if ($f->hasDefaultValue()):
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

    // @@protoc_insertion_point(scope_extension)
    // @@protoc_insertion_point(extension_<?=$namespace?>:<?=$f->name?>)
    
    return $f;
});

<? endforeach ?>
