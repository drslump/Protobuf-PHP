<?php

require_once __DIR__ . '/../library/DrSlump/Protobuf.php';

use \DrSlump\Protobuf;

Protobuf::autoload();

include_once __DIR__ . '/protos/simple.php';
include_once __DIR__ . '/protos/repeated.php';
include_once __DIR__ . '/protos/addressbook.php';


class XmlCodecTests extends \PHPUnit_Framework_TestCase
{
    function setUp()
    {
        Protobuf::setDefaultCodec(new ProtoBuf\Codec\Xml);
    }

    function testSerializeSimpleMessage()
    {
        $simple = new Tests\Simple();
        $simple->string = 'FOO';
        $simple->int32 = 1000;
        $xml = Protobuf::encode($simple);
        $sxe = simplexml_load_string($xml);
        $this->assertEquals($sxe->string, "FOO");
        $this->assertEquals((double)$sxe->int32, 1000);
    }

    function testSerializeMessageWithRepeatedFields()
    {
        $repeated = new \Tests\Repeated();
        $repeated->addString('one');
        $repeated->addString('two');
        $repeated->addString('three');
        $xml = Protobuf::encode($repeated);
        $xml = simplexml_load_string($xml);
        $this->assertEquals((string)$xml->string[0], 'one');
        $this->assertEquals((string)$xml->string[1], 'two');
        $this->assertEquals((string)$xml->string[2], 'three');

        $repeated = new Tests\Repeated();
        $repeated->addInt(1);
        $repeated->addInt(2);
        $repeated->addInt(3);
        $xml = Protobuf::encode($repeated);
        $xml = simplexml_load_string($xml);
        $this->assertEquals((string)$xml->int[0], 1);
        $this->assertEquals((string)$xml->int[1], 2);
        $this->assertEquals((string)$xml->int[2], 3);

        $repeated = new Tests\Repeated();
        $nested = new Tests\Repeated\Nested();
        $nested->setId(1);
        $repeated->addNested($nested);
        $nested = new Tests\Repeated\Nested();
        $nested->setId(2);
        $repeated->addNested($nested);
        $nested = new Tests\Repeated\Nested();
        $nested->setId(3);
        $repeated->addNested($nested);
        $xml = Protobuf::encode($repeated);
        $xml = simplexml_load_string($xml);
        $this->assertEquals((int)$xml->nested[0]->id, 1);
        $this->assertEquals((int)$xml->nested[1]->id, 2);
        $this->assertEquals((int)$xml->nested[2]->id, 3);
    }

    function testSerializeComplexMessage()
    {
        $book = new Tests\AddressBook();
        $person = new Tests\Person();
        $person->name = 'John Doe';
        $person->id = 2051;
        $person->email = 'john.doe@gmail.com';
        $phone = new Tests\Person\PhoneNumber;
        $phone->number = '1231231212';
        $phone->type = Tests\Person\PhoneType::HOME;
        $person->addPhone($phone);
        $phone = new Tests\Person\PhoneNumber;
        $phone->number = '55512321312';
        $phone->type = Tests\Person\PhoneType::MOBILE;
        $person->addPhone($phone);
        $book->addPerson($person);

        $person = new Tests\Person();
        $person->name = 'Iván Montes';
        $person->id = 23;
        $person->email = 'drslump@pollinimini.net';
        $phone = new Tests\Person\PhoneNumber;
        $phone->number = '3493123123';
        $phone->type = Tests\Person\PhoneType::WORK;
        $person->addPhone($phone);
        $book->addPerson($person);

        $xml = Protobuf::encode($book);
        $xml = simplexml_load_string($xml);

        $this->assertEquals($xml->person[0]->name, "John Doe");
        $this->assertEquals($xml->person[0]->phone[1]->number, "55512321312");
        $this->assertEquals((int)$xml->person[1]->id, 23);
        $this->assertEquals((int)$xml->person[1]->phone[0]->type, 2);

    }

    function testUnserializeSimpleMessage()
    {
        $xml = new SimpleXmlElement('<root></root>');
        $xml->addChild('string', 'FOO');
        $xml->addChild('int32', 1000);

        $simple = Protobuf::decode('Tests\Simple', $xml);
        $this->assertInstanceOf('Tests\Simple', $simple);
        $this->assertEquals($simple->string, 'FOO');
        $this->assertEquals($simple->int32, 1000);
    }

    function testUnserializeMessageWithRepeatedFields()
    {
        $xml = new SimpleXMLElement('<root></root>');
        $xml->addChild('string', 'one');
        $xml->addChild('string', 'two');
        $xml->addChild('string', 'three');

        $repeated = Protobuf::decode('Tests\Repeated', $xml);
        $this->assertEquals($repeated->getString(), array('one', 'two', 'three'));

        $xml = new SimpleXMLElement('<root></root>');
        $xml->addChild('int', 1);
        $xml->addChild('int', 2);
        $xml->addChild('int', 3);

        $repeated = Protobuf::decode('Tests\Repeated', $xml);
        $this->assertInstanceOf('Tests\Repeated', $repeated);
        $this->assertEquals($repeated->getInt(), array(1,2,3));

        $xml = new SimpleXMLElement('<root></root>');
        $xml->addChild('nested')->addChild('id', 1);
        $xml->addChild('nested')->addChild('id', 2);
        $xml->addChild('nested')->addChild('id', 3);

        $repeated = Protobuf::decode('Tests\Repeated', $xml);
        $this->assertInstanceOf('Tests\Repeated', $repeated);
        foreach ($repeated->getNested() as $i=>$nested) {
            $this->assertEquals($nested->getId(), $i+1);
        }
    }

    function testUnserializeComplexMessage()
    {
        $xml = new SimpleXMLElement('<root></root>');
        $p = $xml->addChild('person');
            $p->addChild('name', 'John Doe');
            $p->addChild('id', 2051);
            $p->addChild('email', 'john.doe@gmail.com');
            $p = $p->addChild('phone');
                $p->addChild('number', '1231231212');
                $p->addChild('type', 1);
        $p = $xml->addChild('person');
            $p->addChild('name', 'Iván Montes');
            $p->addChild('id', 23);
            $p->addChild('email', 'drslump@pollinimini.net');
            $p = $p->addChild('phone');
                $p->addChild('number', '3493123123');
                $p->addChild('type', 2);

        $complex = Protobuf::decode('Tests\AddressBook', $xml->asXML());
        $this->assertEquals(count($complex->person), 2);
        $this->assertEquals($complex->getPerson(0)->name, 'John Doe');
        $this->assertEquals($complex->getPerson(1)->name, 'Iván Montes');
        $this->assertEquals($complex->getPerson(1)->getPhone(0)->number, '3493123123');
    }
}
