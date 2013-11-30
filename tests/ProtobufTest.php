<?php

require_once __DIR__ . '/../library/DrSlump/Protobuf.php';

use DrSlump\Protobuf;

Protobuf::autoload();


class ProtobufTests extends \PHPUnit_Framework_TestCase
{
    function testAutoloadClasses()
    {
        new Protobuf\Codec\Binary();
    }

    function testGetDefaultCodecIfNoneSet()
    {
        $codec = Protobuf::getCodec();
        $this->assertInstanceOf('\DrSlump\Protobuf\CodecInterface', $codec);
    }

    function testReturnPassedCodecInstance()
    {
        $passed = new Protobuf\Codec\Binary();
        $getted = Protobuf::getCodec($passed);
        $this->assertEquals($getted, $passed);
    }

    function testRegisterNewCodec()
    {
        $setted = new Protobuf\Codec\Binary();
        Protobuf::registerCodec('test', $setted);
        $getted = Protobuf::getCodec('test');
        $this->assertEquals($getted, $setted);
    }

    function testRegisterNewDefaultCodec()
    {
        $setted = new Protobuf\Codec\Binary();
        Protobuf::setDefaultCodec($setted);
        $this->assertEquals(Protobuf::getCodec(), $setted);
    }

    /**
     * @expectedException DrSlump\Protobuf\Exception
     */
    function testUnregisterCodec()
    {
        $setted = new Protobuf\Codec\Binary();
        Protobuf::registerCodec('test', $setted);
        $result = Protobuf::unregisterCodec('test');
        $this->assertTrue($result);
        // If not set throws an exception
        Protobuf::getCodec('test');
    }

    function testUnregisterDefaultCodec()
    {
        $result = Protobuf::unregisterCodec('default');
        $this->assertTrue($result);
        // Ensure a new default is given
        $getted = Protobuf::getCodec();
        $this->assertInstanceOf('DrSlump\Protobuf\Codec\Binary', $getted);
    }

}
