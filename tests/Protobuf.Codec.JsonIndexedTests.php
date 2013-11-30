<?php

require_once __DIR__ . '/../library/DrSlump/Protobuf.php';

use \DrSlump\Protobuf;

Protobuf::autoload();

include_once __DIR__ . '/protos/simple.php';
include_once __DIR__ . '/protos/repeated.php';
include_once __DIR__ . '/protos/addressbook.php';


class JsonIndexedCodecTests extends \PHPUnit_Framework_TestCase
{
    function setUp()
    {
        Protobuf::setDefaultCodec(new Protobuf\Codec\JsonIndexed);
    }

    function testSerializeSimpleMessage()
    {
        $simple = new Tests\Simple();
        $simple->string = 'FOO';
        $simple->int32 = 1000;
        $json = Protobuf::encode($simple);
        $this->assertEquals($json, '["59",1000,"FOO"]');
    }

    function testSerializeMessageWithRepeatedFields()
    {
        $repeated = new Tests\Repeated();
        $repeated->addString('one');
        $repeated->addString('two');
        $repeated->addString('three');
        $bin = Protobuf::encode($repeated);
        $this->assertEquals($bin, '["1",["one","two","three"]]');

        $repeated = new Tests\Repeated();
        $repeated->addInt(1);
        $repeated->addInt(2);
        $repeated->addInt(3);
        $bin = Protobuf::encode($repeated);
        $this->assertEquals($bin, '["2",[1,2,3]]');

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
        $this->assertEquals($json, '["3",[["1",1],["1",2],["1",3]]]');
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

        $expected = '[
             "1",
             [
                 [
                     "1234",
                     "John Doe",
                     2051,
                     "john.doe@gmail.com",
                     [
                         ["12","1231231212",1],
                         ["12","55512321312",0]
                     ]
                 ],
                 [
                     "1234",
                     "Iv\u00e1n Montes",
                     23,
                     "drslump@pollinimini.net",
                     [
                        ["12","3493123123",2]
                     ]
                 ]
             ]
         ]';

        $expected = preg_replace('/\n\s*/', '', $expected);

        $this->assertEquals($json, $expected);
    }

    function testUnserializeSimpleMessage()
    {
        $json = '["59",1000,"FOO"]';
        $simple = Protobuf::decode('Tests\Simple', $json);
        $this->assertInstanceOf('Tests\Simple', $simple);
        $this->assertEquals($simple->string, 'FOO');
        $this->assertEquals($simple->int32, 1000);
    }

    function testUnserializeMessageWithRepeatedFields()
    {
        $json = '["1",["one","two","three"]]';
        $repeated = Protobuf::decode('Tests\Repeated', $json);
        $this->assertEquals($repeated->getString(), array('one', 'two', 'three'));

        $json = '["2",[1,2,3]]';
        $repeated = Protobuf::decode('Tests\Repeated', $json);
        $this->assertInstanceOf('Tests\Repeated', $repeated);
        $this->assertEquals($repeated->getInt(), array(1,2,3));

        $json = '["3",[["1",1],["1",2],["1",3]]]';
        $repeated = Protobuf::decode('Tests\Repeated', $json);
        $this->assertInstanceOf('Tests\Repeated', $repeated);
        foreach ($repeated->getNested() as $i=>$nested) {
            $this->assertEquals($nested->getId(), $i+1);
        }
    }

    function testUnserializeComplexMessage()
    {
        $json = '[
              "1",
              [
                  [
                      "1234",
                      "John Doe",
                      2051,
                      "john.doe@gmail.com",
                      [
                          ["12","1231231212",1],
                          ["12","55512321312",0]
                      ]
                  ],
                  [
                      "1234",
                      "Iv\u00e1n Montes",
                      23,
                      "drslump@pollinimini.net",
                      [
                         ["12","3493123123",2]
                      ]
                  ]
              ]
        ]';

        $json = preg_replace('/\n\s*/', '', $json);

        $complex = Protobuf::decode('Tests\AddressBook', $json);
        $this->assertEquals(count($complex->person), 2);
        $this->assertEquals($complex->getPerson(0)->name, 'John Doe');
        $this->assertEquals($complex->getPerson(1)->name, 'Iván Montes');
        $this->assertEquals($complex->getPerson(0)->getPhone(1)->number, '55512321312');
    }
}
