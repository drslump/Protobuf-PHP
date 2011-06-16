<?php

namespace DrSlump\Protobuf\Codec;

use DrSlump\Protobuf;

class Json extends PhpArray
    implements Protobuf\CodecInterface
{
    /**
     * @param \DrSlump\Protobuf\Message $message
     * @return string
     */
    public function encode(Protobuf\Message $message)
    {
        $data = $this->encodeMessage($message);
        return json_encode($data);
    }

    /**
     * @param \DrSlump\Protobuf\Message $message
     * @param String $data
     * @return \DrSlump\Protobuf\Message
     */
    public function decode(Protobuf\Message $message, $data)
    {
        $data = json_decode($data);
        return $this->decodeMessage($message, $data);
    }

}
