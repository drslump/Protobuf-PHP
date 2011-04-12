<?php

namespace DrSlump\Protobuf;

interface CodecInterface
{
    static public function encode(\DrSlump\Protobuf\Message $message);

    static public function decode($message, $data);

    static public function getInstance();

    public function encodeMessage(\DrSlump\Protobuf\Message $message);
    public function decodeMessage(\DrSlump\Protobuf\Message $message, $data);
}