<?php

namespace DrSlump\Protobuf\Codec\Binary;

/**
 * Implements writing primitives for Protobuf binary streams
 *
 * @note Protobuf uses little-endian order
 */
class Writer
{
    /** @var resource */
    protected $_fd;


    public function __construct()
    {
        $this->_fd = fopen('php://memory', 'wb');
    }

    public function __destruct()
    {
        fclose($this->_fd);
    }

    /**
     * Get the current bytes in the stream
     *
     * @return string
     */
    public function getBytes()
    {
        fseek($this->_fd, 0, SEEK_SET);
        return stream_get_contents($this->_fd);
    }

    /**
     * Store the given bytes in the stream
     *
     * @throws \RuntimeException
     * @param string $bytes
     * @param int $length
     */
    public function write($bytes, $length = null)
    {
        if ($length === NULL) {
            $length = strlen($bytes);
        }

        $written = fwrite($this->_fd, $bytes, $length);
        if ($written !== $length) {
            throw new \RuntimeException('Failed to write ' . $length . ' bytes');
        }
    }

    /**
     * Store a single byte
     *
     * @param int $value
     */
    public function byte($value)
    {
        $this->write(chr($value), 1);
    }

    /**
     * Store a positive integer encoded as varint
     *
     * @throws \OutOfBoundsException
     * @param int $value
     */
    public function varint($value)
    {
        if ($value < 0) {
            throw new \OutOfBoundsException("Varints can only store positive integers but $value was given");
        }

        // Smaller values do not need to be encoded
        if ($value < 128) {
            $this->byte($value);
            return;
        }

        // Build an array of bytes with the encoded values
        $values = array();
        while ($value !== 0) {
            $values[] = 0x80 | ($value & 0x7f);
            $value = $value >> 7;
        }
        $values[count($values)-1] &= 0x7f;

        // Convert the byte sized ints to actual bytes in a string
        $bytes = implode('', array_map('chr', $values));
        //$bytes = call_user_func_array('pack', array_merge(array('C*'), $values));

        $this->write($bytes);
    }

    /**
     * Encodes an integer with zigzag
     *
     * @param int $value
     */
    public function zigzag($value)
    {
        $value = ($value >> 1) ^ (-($value & 1));
        $this->varint($value);
    }

    /**
     * Encode an integer as a fixed of 32bits with sign
     *
     * @param int $value
     */
    public function sFixed32($value)
    {
        $bytes = pack('l*', $value);
        if ($this->isBigEndian()) {
            $bytes = strrev($bytes);
        }

        $this->write($bytes, 4);
    }

    /**
     * Encode an integer as a fixed of 32bits without sign
     *
     * @param int $value
     */
    public function fixed32($value)
    {
        $bytes = pack('N*', $value);
        $this->write($bytes, 4);
    }

    /**
     * Encode an integer as a fixed of 62bits with sign
     *
     * @param int $value
     */
    public function sFixed64($value)
    {
        $bytes = pack('V*', $value & 0xffffffff, $value / (0xffffffff+1));
        $this->write($bytes, 8);
    }

    /**
     * Encode an integer as a fixed of 62bits without sign
     *
     * @param int $value
     */
    public function fixed64($value)
    {
        return $this->sFixed64($value);
    }

    /**
     * Encode a number as a 32bit float
     *
     * @param float $value
     */
    public function float($value)
    {
        $bytes = pack('f*', $value);
        if ($this->isBigEndian()) {
            $bytes = strrev($bytes);
        }
        $this->write($bytes, 4);
    }

    /**
     * Encode a number as a 64bit double
     *
     * @param float $value
     */
    public function double($value)
    {
        $bytes = pack('d*', $value);
        if ($this->isBigEndian()) {
            $bytes = strrev($bytes);
        }
        $this->write($bytes, 8);
    }

    /**
     * Checks if the current arquitecture is Big Endian
     *
     * @return bool
     */
    public function isBigEndian()
    {
        static $endianness;

        if (NULL === $endianness) {
            list(,$result) = unpack('L', pack('V', 1));
            $endianness = $result !== 1;
        }

        return $endianness;
    }
}
