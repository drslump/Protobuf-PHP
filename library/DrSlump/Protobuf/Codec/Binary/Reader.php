<?php

namespace DrSlump\Protobuf\Codec\Binary;

/**
 *
 * @note Protobuf uses little-endian order
 *
 * @throws Exception
 */
class Reader {

    const LITTLE_ENDIAN = 1;
    const BIG_ENDIAN = 2;

    static protected $_endianness = null;

    protected $_fd;

    public function __construct($fdOrString)
    {
        if (is_resource($fdOrString)) {
            $this->_fd = $fdOrString;
        } else {
            // @todo Could this be faster by using a custom String wrapper?
            $this->_fd = fopen('data://text/plain,' . urlencode($fdOrString), 'rb');
        }
    }

    public function __destruct()
    {
        fclose($this->_fd);
    }

    public function read($length)
    {
        $bytes = fread($this->_fd, $length);
        if ($bytes === false) {
            throw new \Exception('Failed to read ' . $length . ' bytes');
        }

        return $bytes;
    }

    public function eof()
    {
        return feof($this->_fd);
    }

    public function pos()
    {
        return ftell($this->_fd);
    }

    public function byte()
    {
        return ord($this->read(1));
    }

    public function varint()
    {
        $result = $shift = 0;
        do {
            $byte = $this->byte();
            $result |= ($byte & 0x7f) << $shift;
            $shift += 7;
        } while ($byte > 0x7f);

        return $result;
    }

    public function sFixed32()
    {
        $bytes = $this->read(4);
        if ($this->isBigEndian() === self::BIG_ENDIAN) {
            $bytes = strrev($bytes);
        }

        list(, $result) = unpack('l', $bytes);
        return $result;
    }

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

    public function sFixed64()
    {
        $bytes = $this->read(8);

        list(, $lo0, $lo1, $hi0, $hi1) = unpack('v*', $bytes);
        return ($hi1 << 16 | $hi0) << 32 | ($lo1 << 16 | $lo0);
    }

    public function fixed64()
    {
        return $this->sFixed64();
    }

    public function float()
    {
        $bytes = $this->read(4);
        if ($this->isBigEndian() === self::BIG_ENDIAN) {
            $bytes = strrev($bytes);
        }

        list(, $result) = unpack('f', $bytes);
        return $result;
    }

    public function double()
    {
        $bytes = $this->read(8);
        if ($this->isBigEndian()) {
            $bytes = strrev($bytes);
        }

        list(, $result) = unpack('d', $bytes);
        return $result;
    }



    public function isBigEndian()
    {
        if (self::$_endianness === NULL) {
            list(,$result) = unpack('L', pack('V', 1));
            if ($result === 1)
                self::$_endianness = self::LITTLE_ENDIAN;
            else {
                self::$_endianness = self::BIG_ENDIAN;
            }
        }
        return self::$_endianness === self::BIG_ENDIAN;
    }


}
