<?php

require_once __DIR__ . '/../library/DrSlump/Protobuf.php';

use \DrSlump\Protobuf;

Protobuf::autoload();

include_once __DIR__ . '/protos/simple.php';
include_once __DIR__ . '/protos/repeated.php';
include_once __DIR__ . '/protos/addressbook.php';

include_once __DIR__ . '/protos/Annotated/Simple.php';
include_once __DIR__ . '/protos/Annotated/Repeated.php';
include_once __DIR__ . '/protos/Annotated/Addressbook.php';


class JsonCodecTests extends \PHPUnit_Framework_TestCase
{
    function setUp()
    {
        Protobuf::setDefaultCodec(new ProtoBuf\Codec\Json);
    }

    function testSerializeSimpleMessage()
    {
        $simple = new Tests\Simple();
        $simple->string = 'FOO';
        $simple->int32 = 1000;
        $json = Protobuf::encode($simple);
        $this->assertEquals($json, '{"int32":1000,"string":"FOO"}');
    }

    function testSerializeMessageWithRepeatedFields()
    {
        $repeated = new \Tests\Repeated();
        $repeated->addString('one');
        $repeated->addString('two');
        $repeated->addString('three');
        $bin = Protobuf::encode($repeated);
        $this->assertEquals($bin, '{"string":["one","two","three"]}');

        $repeated = new Tests\Repeated();
        $repeated->addInt(1);
        $repeated->addInt(2);
        $repeated->addInt(3);
        $bin = Protobuf::encode($repeated);
        $this->assertEquals($bin, '{"int":[1,2,3]}');

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
        $json = Protobuf::encode($repeated);
        $this->assertEquals($json, '{"nested":[{"id":1},{"id":2},{"id":3}]}');
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

        $json = Protobuf::encode($book);

        $expected = '{
            "person":[
                {
                    "name":"John Doe",
                    "id":2051,
                    "email":"john.doe@gmail.com",
                    "phone":[
                        {"number":"1231231212","type":1},
                        {"number":"55512321312","type":0}
                    ]
                },
                {
                    "name":"Iv\u00e1n Montes",
                    "id":23,
                    "email":"drslump@pollinimini.net",
                    "phone":[{"number":"3493123123","type":2}]
                }
            ]
        }';

        $expected = preg_replace('/\n\s*/', '', $expected);

        $this->assertEquals($json, $expected);
    }

    function testSerializeAnnotatedSimpleMessage()
    {
        $simple = new tests\Annotated\Simple();
        $simple->foo = 'FOO';
        $simple->bar = 1000;
        $json = Protobuf::encode($simple);
        $this->assertEquals($json, '{"foo":"FOO","bar":1000}');
    }

    function testSerializeAnnotatedMessageWithRepeatedFields()
    {
        $repeated = new \Tests\Annotated\Repeated();
        $repeated->string = array('one', 'two', 'three');
        $bin = Protobuf::encode($repeated);
        $this->assertEquals($bin, '{"string":["one","two","three"]}');

        $repeated = new Tests\Annotated\Repeated();
        $repeated->int = array(1,2,3);
        $bin = Protobuf::encode($repeated);
        $this->assertEquals($bin, '{"int":[1,2,3]}');

        $repeated = new Tests\Annotated\Repeated();
        $repeated->nested = array();
        $nested = new Tests\Annotated\RepeatedNested();
        $nested->id = 1;
        $repeated->nested[] = $nested;
        $nested = new Tests\Annotated\RepeatedNested();
        $nested->id = 2;
        $repeated->nested[] = $nested;
        $nested = new Tests\Annotated\RepeatedNested();
        $nested->id = 3;
        $repeated->nested[] = $nested;
        $json = Protobuf::encode($repeated);
        $this->assertEquals($json, '{"nested":[{"id":1},{"id":2},{"id":3}]}');
    }

    function testUnserializeSimpleMessage()
    {
        $json = '{"string":"FOO","int32":1000}';
        $simple = Protobuf::decode('Tests\Simple', $json);
        $this->assertInstanceOf('Tests\Simple', $simple);
        $this->assertEquals($simple->string, 'FOO');
        $this->assertEquals($simple->int32, 1000);
    }

    function testUnserializeMessageWithRepeatedFields()
    {
        $json = '{"string":["one","two","three"]}';
        $repeated = Protobuf::decode('Tests\Repeated', $json);
        $this->assertEquals($repeated->getString(), array('one', 'two', 'three'));

        $json = '{"int":[1,2,3]}';
        $repeated = Protobuf::decode('Tests\Repeated', $json);
        $this->assertInstanceOf('Tests\Repeated', $repeated);
        $this->assertEquals($repeated->getInt(), array(1,2,3));

        $json = '{"nested":[{"id":1},{"id":2},{"id":3}]}';
        $repeated = Protobuf::decode('Tests\Repeated', $json);
        $this->assertInstanceOf('Tests\Repeated', $repeated);
        foreach ($repeated->getNested() as $i=>$nested) {
            $this->assertEquals($nested->getId(), $i+1);
        }
    }

    function testUnserializeComplexMessage()
    {
        $json = '{
            "person":[
                {
                    "name":"John Doe",
                    "id":2051,
                    "email":"john.doe@gmail.com",
                    "phone":[
                        {"number":"1231231212","type":1},
                        {"number":"55512321312","type":0}
                    ]
                },
                {
                    "name":"Iv\u00e1n Montes",
                    "id":23,
                    "email":"drslump@pollinimini.net",
                    "phone":[{"number":"3493123123","type":2}]
                }
            ]
        }';

        $json = preg_replace('/\n\s*/', '', $json);

        $complex = Protobuf::decode('Tests\AddressBook', $json);
        $this->assertEquals(count($complex->person), 2);
        $this->assertEquals($complex->getPerson(0)->name, 'John Doe');
        $this->assertEquals($complex->getPerson(1)->name, 'Iván Montes');
        $this->assertEquals($complex->getPerson(0)->getPhone(1)->number, '55512321312');
    }
}
