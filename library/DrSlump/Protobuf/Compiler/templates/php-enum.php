<?php /*

    Receives the following variables:

        $namespace - The current namespace (aka package)
        $data      - A google.protobuf.EnumDescriptorProto object

    */ ?>
namespace <?php echo $this->ns($namespace)?> {

    // @@protoc_insertion_point(scope_namespace)
    // @@protoc_insertion_point(namespace_<?php echo $namespace?>)

    <?php $ns = $namespace . '.' . $data->name; ?>
    <?php if ($this->comment($ns)): ?>
    /**
     * <?php echo $this->comment($ns, '     *')?> 
     */
    <?php endif ?>
    class <?php echo $data->name?> extends \DrSlump\Protobuf\Enum
    {
        <?php foreach ($data->value as $value): ?>
        const <?php echo $value->name?> = <?php echo $value->number?>;
        <?php endforeach ?>

        // @@protoc_insertion_point(scope_class)";
        // @@protoc_insertion_point(class_<?php echo $ns?>)
    }
}
 
