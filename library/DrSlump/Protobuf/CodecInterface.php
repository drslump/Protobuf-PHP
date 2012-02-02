<?php

namespace DrSlump\Protobuf;

interface CodecInterface
{
    public function encode(\DrSlump\Protobuf\MessageInterface $message);
    public function decode(\DrSlump\Protobuf\MessageInterface $message, $data);
}
