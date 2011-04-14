<?php

require_once __DIR__ . '/../library/DrSlump/Protobuf.php';

use \DrSlump\Protobuf;

Protobuf::autoload();

include_once __DIR__ . '/protos/simple.php';

describe "JSON HashTag Codec"

    describe "serialize"

        it "should serialize a simple message"
            $simple = new Tests\Simple();
            $simple->foo = 'FOO';
            $simple->bar = 'BAR';
            $json = Protobuf\Codec\JsonTagMap::encode($simple);
            $json. should. eq. '{"1":"FOO","2":"BAR"}';
        end.

        it. "a message with repeated fields"

             $repeated = new Tests\Repeated();
             $repeated->addString('one');
             $repeated->addString('two');
             $repeated->addString('three');
             $bin = Protobuf\Codec\JsonTagMap::encode($repeated);
             $bin should be '{"1":["one","two","three"]}';

             $repeated = new Tests\Repeated();
             $repeated->addInt(1);
             $repeated->addInt(2);
             $repeated->addInt(3);
             $bin = Protobuf\Codec\JsonTagMap::encode($repeated);
             $bin should be '{"2":[1,2,3]}';

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
             $json = Protobuf\Codec\JsonTagMap::encode($repeated);
             $json should eq '{"3":[{"1":1},{"1":2},{"1":3}]}';
         end.

        it. "a complex message"

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

            $json = Protobuf\Codec\JsonTagMap::encode($book);

            $expected = '{
                "1":[
                    {
                        "1":"John Doe",
                        "2":2051,
                        "3":"john.doe@gmail.com",
                        "4":[
                            {"1":"1231231212","2":1},
                            {"1":"55512321312","2":0}
                        ]
                    },
                    {
                        "1":"Iv\u00e1n Montes",
                        "2":23,
                        "3":"drslump@pollinimini.net",
                        "4":[{"1":"3493123123","2":2}]
                    }
                ]
            }';

            $expected = preg_replace('/\n\s*/', '', $expected);

            $json should be $expected;
        end.
    end;

    describe "unserialize"

        it "should unserialize a simple message"
            $json = '{"1":"FOO","2":"BAR"}';
            $simple = Protobuf\Codec\JsonTagMap::decode('Tests\Simple', $json);
            $simple should be instanceof 'Tests\Simple';
            $simple->foo should equal 'FOO';
            $simple->bar should equal 'BAR';
        end.

        it "a message with repeated fields"

            $json = '{"1":["one","two","three"]}';
            $repeated = Protobuf\Codec\JsonTagMap::decode('Tests\Repeated', $json);
            $repeated->getString() should eq array('one', 'two', 'three');

            $json = '{"2":[1,2,3]}';
            $repeated = Protobuf\Codec\JsonTagMap::decode('Tests\Repeated', $json);
            $repeated should be instanceof 'Tests\Repeated';
            $repeated->getInt() should eq array(1,2,3);

            $json = '{"3":[{"1":1},{"1":2},{"1":3}]}';
            $repeated = Protobuf\Codec\JsonTagMap::decode('Tests\Repeated', $json);
            $repeated should be instanceof 'Tests\Repeated';
            foreach ($repeated->getNested() as $i=>$nested) {
                $nested->getId() should eq ($i+1);
            }
        end.

        it "a complex message"
            $json = '{
                "1":[
                    {
                        "1":"John Doe",
                        "2":2051,
                        "3":"john.doe@gmail.com",
                        "4":[
                            {"1":"1231231212","2":1},
                            {"1":"55512321312","2":0}
                        ]
                    },
                    {
                        "1":"Iv\u00e1n Montes",
                        "2":23,
                        "3":"drslump@pollinimini.net",
                        "4":[{"1":"3493123123","2":2}]
                    }
                ]
            }';

            $json = preg_replace('/\n\s*/', '', $json);

            $complex = Protobuf\Codec\JsonTagMap::decode('Tests\AddressBook', $json);
            count($complex->person) should eq 2;
            $complex->getPerson(0)->name should eq 'John Doe';
            $complex->getPerson(1)->name should eq 'Iván Montes';
            $complex->getPerson(0)->getPhone(1)->number should eq '55512321312';
        end.

    end;
end;
