<?php

require_once __DIR__ . '/../library/DrSlump/Protobuf.php';

use \DrSlump\Protobuf;

Protobuf::autoload();

include_once __DIR__ . '/protos/complex.php';


class EnumTests extends \PHPUnit_Framework_TestCase
{
    function setUp()
    {
        Protobuf::setDefaultCodec(new ProtoBuf\Codec\Json);
    }

    function testObtainAllPossibleEnumValues()
    {
        $enum = Tests\Complex\Enum::getInstance();
        $this->assertInternalType('array', $enum->toArray());
        $this->assertEquals(count($enum->toArray()), 3);

        $arr = $enum->toArray();
        foreach ($enum as $k=>$v) {
            $this->assertEquals($v, $arr[$k]);
        }
    }

    function testAccessWithPropertyNamesAndWithArrayOffsets()
    {
        $enum = Tests\Complex\Enum::getInstance();
        $this->assertEquals($enum->FOO, $enum['FOO']);
    }

    function testObtainEnumValueNameFromTheIntegerValue()
    {
        $enum = Tests\Complex\Enum::getInstance();
        $this->assertEquals($enum[ $enum->FOO ], 'FOO');
    }
}
