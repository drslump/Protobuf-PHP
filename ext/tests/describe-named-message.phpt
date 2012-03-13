--TEST--
Define a new message with a name
--FILE--
<?php
extension_loaded('protobuf') or dl('protobuf.' . PHP_SHLIB_SUFFIX);

$r = protobuf_desc_message("test");
is_resource($r) and print("RESOURCE");
?>
--EXPECT--
RESOURCE
