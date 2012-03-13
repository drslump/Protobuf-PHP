<? /*

    Receives the following variables:

        $namespace - The current namespace (aka package)
        $data      - A google.protobuf.EnumDescriptorProto object

    */ ?>
namespace <?=$this->ns($namespace)?> {

    // @@protoc_insertion_point(scope_namespace)
    // @@protoc_insertion_point(namespace_<?=$namespace?>)

    <? $ns = $namespace . '.' . $data->name; ?>
    <? if ($this->comment($ns)): ?>
    /**
     * <?=$this->comment($ns, '     *')?> 
     */
    <? endif ?>
    class <?=$data->name?> extends \DrSlump\Protobuf\Enum
    {
        <? foreach ($data->value as $value): ?>
        const <?=$value->name?> = <?=$value->number?>;
        <? endforeach ?>

        // @@protoc_insertion_point(scope_class)";
        // @@protoc_insertion_point(class_<?=$ns?>)
    }
}
 
