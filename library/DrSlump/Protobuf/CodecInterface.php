<?php

namespace DrSlump\Protobuf;

interface CodecInterface
{
    static public function encode(\DrSlump\Protobuf\Message $message);

    static public function decode($message, $data);
}