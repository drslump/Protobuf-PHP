--TEST--
Check for protobuf extension presence
--FILE--
<?php
extension_loaded('protobuf') or dl('protobuf.' . PHP_SHLIB_SUFFIX);

extension_loaded('protobuf') and print("protobuf extension is available");
?>
--EXPECT--
protobuf extension is available
