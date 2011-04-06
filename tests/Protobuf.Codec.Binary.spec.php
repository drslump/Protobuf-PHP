<?php

require_once __DIR__ . '/../library/DrSlump/Protobuf.php';

use \DrSlump\Protobuf;

Protobuf::autoload();

include_once __DIR__ . '/protos/simple.php';

describe "Binary Codec"

    before
        $W->bin_simple = file_get_contents(__DIR__ . '/protos/simple.bin');
    end;

    describe "serialize"

        it "should serialize a simple message"

            $simple = new Tests\Simple();
            $simple->foo = 'FOO';
            $simple->bar = 100;
            $simple->baz = 'BAZ';
            $bin = Protobuf\Codec\Binary::encode($simple);
            $bin should be $W->bin_simple but not be false;
        end.

    end;

    describe "unserialize"

        it "should unserialize a simple message"
            $simple = Protobuf\Codec\Binary::decode('Tests\Simple', $W->bin_simple);
            $simple should be instanceof 'Tests\Simple';
            $simple->foo should be 'FOO';
            $simple->bar should be 100;
            $simple->baz should be 'BAZ';
        end.

    end;
end;
