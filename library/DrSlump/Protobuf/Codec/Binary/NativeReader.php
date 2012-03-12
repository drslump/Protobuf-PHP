<?php

namespace DrSlump\Protobuf\Codec\Binary;

/**
 * Implements reading primitives for Protobuf binary streams
 *
 * Important: There are no checks in place for overflows, so you must
 * be aware of PHP's integer and floating point limits.
 *
 * @note Protobuf uses little-endian order
 */
class NativeReader
{
    protected $_str = '';
    protected $_len = 0;
    protected $_ofs = 0;

    /**
     * Create a new reader from a file descriptor or a string of bytes
     *
     * @param resource|string $fdOrString
     */
    public function __construct($fdOrString = NULL)
    {
        if (NULL !== $fdOrString) {
            $this->init($fdOrString);
        }
    }

    public function __destruct()
    {
    }

    /**
     * Create a new reader from a file descriptor or a string of bytes
     *
     * @param resource|string $fdOrString
     */
    public function init($fdOrString)
    {
        if (is_resource($fdOrString)) {
            $this->_str = stream_get_contents($fdOrString);
        } else {
            $this->_str = $fdOrString;
        }

        $this->_len = strlen($this->_str);
        $this->_ofs = 0;
    }

    /**
     * Obtain a number of bytes from the string
     *
     * @throws \RuntimeException
     * @param int $length
     * @return string
     */
    public function read($length)
    {
        // Protect against 0 byte reads when an EOF
        if ($length < 1) return '';

        $bytes = substr($this->_str, $this->_ofs, $length);
        $this->_ofs += strlen($bytes);

        return $bytes;
    }

    /**
     * Check if we have reached the end of the stream
     *
     * @return bool
     */
    public function eof()
    {
        return $this->_ofs >= $this->_len;
    }

    /**
     * Obtain the current position in the stream
     *
     * @return int
     */
    public function pos()
    {
        return $this->_ofs;
    }

    /**
     * Obtain a byte
     *
     * @return int
     */
    public function byte()
    {
        // Optimization: Avoid a call to read() by accessing directly the string by index
        return ord($this->_str[$this->_ofs++]);
    }

    /**
     * Decode a varint
     *
     * @return int
     */
    public function varint()
    {
        // Optimize common case (single byte varints)
        $b = ord($this->_str[$this->_ofs++]);
        if ($b < 0x80) {
            return $b;
        }

        // Work with references to avoid the evaluating $this-> each time
        $str = &$this->_str;
        $ofs = &$this->_ofs;

        $r = $b & 0x7f;
        $s = 7;

        // Optimize 32bit varints (5bytes) by unrolling the loop
        if ($this->_len - $ofs >= 4) {
            $b = ord($str[$ofs++]); $r |= ($b & 0x7f) << 7;  if ($b < 0x80) return $r;
            $b = ord($str[$ofs++]); $r |= ($b & 0x7f) << 14; if ($b < 0x80) return $r;
            $b = ord($str[$ofs++]); $r |= ($b & 0x7f) << 21; if ($b < 0x80) return $r;
            $b = ord($str[$ofs++]); $r |= ($b & 0x7f) << 28; if ($b < 0x80) return $r;
            $s = 35;
        }

        // If we're just at the end of the buffer or handling a 64bit varint
        do {
            $b = ord($str[$ofs++]); 
            $r |= ($b & 0x7f) << $s; 
            $s += 7;
        } while ($b > 0x7f);

        return $r;
    }

    /**
     * Decodes a zigzag integer of the given bits
     *
     * @param int $bits - Either 32 or 64
     */
    public function zigzag()
    {
        $number = $this->varint();
        return ($number >> 1) ^ (-($number & 1));
    }

    /**
     * Decode a fixed 32bit integer with sign
     *
     * @return int
     */
    public function sFixed32()
    {
        $bytes = $this->read(4);
        if ($this->isBigEndian()) {
            $bytes = strrev($bytes);
        }

        list(, $result) = unpack('l', $bytes);
        return $result;
    }

    /**
     * Decode a fixed 32bit integer without sign
     *
     * @return int
     */
    public function fixed32()
    {
        $bytes = $this->read(4);

        if (PHP_INT_SIZE < 8) {
            list(, $lo, $hi) = unpack('v*', $bytes);
            $result = $hi << 16 | $lo;
        } else {
            list(, $result) = unpack('V*', $bytes);
        }

        return $result;
    }

    /**
     * Decode a fixed 64bit integer with sign
     *
     * @return int
     */
    public function sFixed64()
    {
        $bytes = $this->read(8);

        list(, $lo0, $lo1, $hi0, $hi1) = unpack('v*', $bytes);
        return ($hi1 << 16 | $hi0) << 32 | ($lo1 << 16 | $lo0);
    }

    /**
     * Decode a fixed 64bit integer without sign
     *
     * @return int
     */
    public function fixed64()
    {
        return $this->sFixed64();
    }

    /**
     * Decode a 32bit float
     *
     * @return float
     */
    public function float()
    {
        $bytes = $this->read(4);
        if ($this->isBigEndian()) {
            $bytes = strrev($bytes);
        }

        list(, $result) = unpack('f', $bytes);
        return $result;
    }

    /**
     * Decode a 64bit double
     *
     * @return float
     */
    public function double()
    {
        $bytes = $this->read(8);
        if ($this->isBigEndian()) {
            $bytes = strrev($bytes);
        }

        list(, $result) = unpack('d', $bytes);
        return $result;
    }

    /**
     * Check if the current architecture is Big Endian
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
