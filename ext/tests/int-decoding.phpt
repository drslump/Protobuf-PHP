--TEST--
Tests the encoding of int32 and int64 types including negative values
--FILE--
<?php
extension_loaded('protobuf') or dl('protobuf.' . PHP_SHLIB_SUFFIX);

// Create a message descriptor resource
$m = protobuf_desc_message();
// msg(res), num(int), label(int), type(int), [name, [flag(int), [nested(res)]]] : bool
protobuf_desc_field($m, 1, 1 /*optional*/, 5 /*INT32*/, 'int32');
protobuf_desc_field($m, 2, 1 /*optiona*/, 3 /*INT64*/, 'int64');

// int32: -1, int64: -1
//$binary = pack('H*', '08ffffffffffffffffff0110ffffffffffffffffff01');
$binary = pack('H*', '1083d6ffffffffffffff010883d6ffffffffffffff01');

$data = protobuf_decode($m, $binary);
print 'int32: ' . $data['int32'] . PHP_EOL;
print 'int64: ' . $data['int64'] . PHP_EOL;

?>
--EXPECT--
int32: -5373
int64: -5373
