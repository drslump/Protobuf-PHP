--TEST--
Check for pbext presence
--FILE--
<?php
extension_loaded('pbext') or dl('pbext.' . PHP_SHLIB_SUFFIX);

extension_loaded('pbext') and print("pbext extension is available");
?>
--EXPECT--
pbext extension is available
