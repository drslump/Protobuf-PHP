--TEST--
Define a new message
--FILE--
<?php
extension_loaded('protobuf') or dl('protobuf.' . PHP_SHLIB_SUFFIX);

$r = protobuf_desc_message();
is_resource($r) and print("RESOURCE");
?>
--EXPECT--
RESOURCE
