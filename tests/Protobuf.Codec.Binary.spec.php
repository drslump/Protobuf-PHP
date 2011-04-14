<?php

require_once __DIR__ . '/../library/DrSlump/Protobuf.php';

use \DrSlump\Protobuf;

Protobuf::autoload();

include_once __DIR__ . '/protos/simple.php';
include_once __DIR__ . '/protos/repeated.php';
include_once __DIR__ . '/protos/addressbook.php';

describe "Binary Codec"

    before
        $W->bin_simple = file_get_contents(__DIR__ . '/protos/simple.bin');
        $W->bin_book = file_get_contents(__DIR__ . '/protos/addressbook.bin');
        $W->bin_repeated_string = file_get_contents(__DIR__ . '/protos/repeated-string.bin');
        $W->bin_repeated_int32 = file_get_contents(__DIR__ . '/protos/repeated-int32.bin');
        $W->bin_repeated_nested = file_get_contents(__DIR__ . '/protos/repeated-nested.bin');
    end;

    describe "serialize"

        it "a simple message"

            $simple = new Tests\Simple();
            $simple->foo = 'FOO';
            $simple->bar = 100;
            $simple->baz = 'BAZ';
            $bin = Protobuf\Codec\Binary::encode($simple);
            $bin should be $W->bin_simple but not be false;
        end.

        it. "a message with repeated fields"

            $repeated = new Tests\Repeated();
            $repeated->addString('one');
            $repeated->addString('two');
            $repeated->addString('three');
            $bin = Protobuf\Codec\Binary::encode($repeated);
            $bin should be $W->bin_repeated_string;

            $repeated = new Tests\Repeated();
            $repeated->addInt(1);
            $repeated->addInt(2);
            $repeated->addInt(3);
            $bin = Protobuf\Codec\Binary::encode($repeated);
            $bin should be $W->bin_repeated_int32;


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
            $bin = Protobuf\Codec\Binary::encode($repeated);
            $bin should eq $W->bin_repeated_nested;
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

            $bin = Protobuf\Codec\Binary::encode($book);
            $bin should eq $W->bin_book but not be false;

        end.

    end;

    describe "unserialize"

        it "a simple message"
            $simple = Protobuf\Codec\Binary::decode('Tests\Simple', $W->bin_simple);
            $simple should be instanceof 'Tests\Simple';
            $simple->foo should be 'FOO';
            $simple->bar should be 100;
            $simple->baz should be 'BAZ';
        end.

        it "a message with repeated fields"

            $repeated = Protobuf\Codec\Binary::decode('Tests\Repeated', $W->bin_repeated_string);
            $repeated should be instanceof 'Tests\Repeated';
            $repeated->getString() should eq array('one', 'two', 'three');

            $repeated = Protobuf\Codec\Binary::decode('Tests\Repeated', $W->bin_repeated_int32);
            $repeated should be instanceof 'Tests\Repeated';
            $repeated->getInt() should eq array(1,2,3);

            $repeated = Protobuf\Codec\Binary::decode('Tests\Repeated', $W->bin_repeated_nested);
            $repeated should be instanceof 'Tests\Repeated';
            foreach ($repeated->getNested() as $i=>$nested) {
                $nested->getId() should eq ($i+1);
            }
        end.

        it "a complex message"
            $complex = Protobuf\Codec\Binary::decode('Tests\AddressBook', $W->bin_book);
            count($complex->person) should eq 2;
            $complex->getPerson(0)->name should eq 'John Doe';
            $complex->getPerson(1)->name should eq 'Iván Montes';
            $complex->getPerson(0)->getPhone(1)->number should eq '55512321312';
        end.

    end;

    describe "multi codec"

        it "a simple message"

            $simple = Protobuf\Codec\Binary::decode('Tests\Simple', $W->bin_simple);
            $json = Protobuf\Codec\Json::encode($simple);
            $simple = Protobuf\Codec\Json::decode('Tests\Simple', $json);
            $bin = Protobuf\Codec\Binary::encode($simple);
            $bin should be $W->bin_simple;

        end.

        it "a message with repeated fields"
            $repeated = Protobuf\Codec\Binary::decode('Tests\Repeated', $W->bin_repeated_nested);
            $json = Protobuf\Codec\Json::encode($repeated);
            $repeated = Protobuf\Codec\Json::decode('Tests\Repeated', $json);
            $bin = Protobuf\Codec\Binary::encode($repeated);
            $bin should be $W->bin_repeated_nested;
        end.

    end;
end;
