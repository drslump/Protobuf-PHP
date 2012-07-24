<?php /*

    Receives the following variables:

        $namespace - The current namespace (aka package)
        $data      - A google.protobuf.ServiceDescriptorProto object

    */ ?>
namespace <?php echo $this->ns($namespace)?> {

    // @@protoc_insertion_point(scope_namespace)
    // @@protoc_insertion_point(namespace_<?php echo $namespace?>)

    <?php $ns = $namespace . '.' . $data->name; ?>
    <?php if ($this->comment($ns)): ?>
    /**
     * <?php echo $this->comment($ns, '     * ')?> 
     */
    <?php endif ?>
    interface <?php echo $data->name?> 
    {
        // @@protoc_insertion_point(scope_interface)
        // @@protoc_insertion_point(interface_<?php echo $ns?>)
        
        <?php foreach ($data->method_list as $method): ?>
        /**
         * <?php echo  $this->comment($ns . '.' . $method->name, '         * '); ?>
         * 
         * @param <?php echo $this->ns($method->input_type)?> $input
         * @return <?php echo $this->ns($method->output_type)?>
         */
        public function <?php echo $method->name?>(<?php echo $this->ns($method->input_type)?> $input);
        <?php endforeach; ?>
    }
}
 
