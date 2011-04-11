<?php

require_once __DIR__ . '/../library/DrSlump/Protobuf.php';

use \DrSlump\Protobuf;

Protobuf::autoload();

include_once __DIR__ . '/protos/simple.php';

describe "JSON Indexed Codec"

    describe "serialize"

        it "should serialize a simple message"
            $simple = new Tests\Simple();
            $simple->foo = 'FOO';
            $simple->bar = 'BAR';
            $json = Protobuf\Codec\JsonIndexed::encode($simple);
            $json. should. eq. '["12","FOO","BAR"]';
        end.

    end;

    describe "unserialize"

        it "should unserialize a simple message"
            $json = '["12","FOO","BAR"]';
            $simple = Protobuf\Codec\JsonIndexed::decode('Tests\Simple', $json);
            $simple should be instanceof 'Tests\Simple';
            $simple->foo should equal 'FOO';
            $simple->bar should equal 'BAR';
        end.

    end;
end;
