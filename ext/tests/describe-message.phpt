--TEST--
Define a new message
--FILE--
<?php
extension_loaded('pbext') or dl('pbext.' . PHP_SHLIB_SUFFIX);

$r = pbext_desc_message();
is_resource($r) and print("RESOURCE");
?>
--EXPECT--
RESOURCE
