<?php

namespace DrSlump\Protobuf;

use DrSlump\Protobuf;

/**
 * Keeps instances of the different message descriptors used.
 *
 */
class Registry
{
    /** @var array */
    protected $descriptors = array();

    /**
     * @param string|\DrSlump\Protobuf\Message $message
     * @param \DrSlump\Protobuf\Descriptor $descriptor
     */
    public function setDescriptor($message, Descriptor $descriptor)
    {
        $message = is_object($message) ? get_class($message) : $message;
        $this->descriptors[$message] = $descriptor;
    }

    /**
     * Obtains the descriptor for the given message class, obtaining
     * it if not yet loaded.
     *
     * @param string|\DrSlump\Protobuf\Message $message
     * @return \DrSlump\Protobuf\Descriptor
     */
    public function getDescriptor($message)
    {
        $message = is_object($message) ? get_class($message) : $message;

        // Build a descriptor for the message
        if (!isset($this->descriptors[$message])) {
            $this->descriptors[$message] = $message::descriptor();
        }

        return $this->descriptors[$message];
    }

    /**
     * @param string|\DrSlump\Protobuf\Message $message
     * @return bool
     */
    public function hasDescriptor($message)
    {
        $message = is_object($message) ? get_class($message) : $message;
        return isset($this->descriptors[$message]);
    }

    /**
     * @param string|\DrSlump\Protobuf\Message $message
     */
    public function unsetDescriptor($message)
    {
        $message = is_object($message) ? get_class($message) : $message;
        unset($this->descriptors[$message]);
    }
}