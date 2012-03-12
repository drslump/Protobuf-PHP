<? /*

    Receives the following variables:

        $namespace - The current namespace (aka package)
        $data      - A google.protobuf.ServiceDescriptorProto object

    */ ?>
namespace <?=$this->ns($namespace)?> {

    // @@protoc_insertion_point(scope_namespace)
    // @@protoc_insertion_point(namespace_<?=$namespace?>)

    <? $ns = $namespace . '.' . $data->name; ?>
    <? if ($this->comment($ns)): ?>
    /**
     * <?=$this->comment($ns, '     * ')?> 
     */
    <? endif ?>
    interface <?=$data->name?> 
    {
        // @@protoc_insertion_point(scope_interface)
        // @@protoc_insertion_point(interface_<?=$ns?>)
        
        <? foreach ($data->method_list as $method): ?>
        /**
         * <?= $this->comment($ns . '.' . $method->name, '         * '); ?>
         * 
         * @param <?=$this->ns($method->input_type)?> $input
         * @return <?=$this->ns($method->output_type)?>
         */
        public function <?=$method->name?>(<?=$this->ns($method->input_type)?> $input);
        <? endforeach; ?>
    }
}
 
