<?php

namespace DrSlump\Protobuf\Codec\Binary;

/**
 *
 * @note Protobuf uses little-endian order
 *
 * @throws Exception
 */
class Writer {

    const LITTLE_ENDIAN = 1;
    const BIG_ENDIAN = 2;

    static protected $_endianness = null;

    protected $_fd;

    public function __construct()
    {
        $this->_fd = fopen('php://memory', 'wb');
    }

    public function __destruct()
    {
        fclose($this->_fd);
    }

    public function getContents()
    {
        fseek($this->_fd, 0, SEEK_SET);
        return stream_get_contents($this->_fd);
    }

    public function write($bytes, $length = null)
    {
        if ($length === NULL) {
            $length = strlen($bytes);
        }

        $written = fwrite($this->_fd, $bytes, $length);
        if ($written !== $length) {
            throw new \Exception('Failed to write ' . $length . ' bytes');
        }
    }

    public function byte($value)
    {
        $this->write(chr($value), 1);
    }

    public function varint($value)
    {
        if ($value < 0) throw new \Exception("$value is negative");

        if ($value < 128) {
            $this->byte($value);
            return;
        }

        $values = array();
        while ($value !== 0) {
            $values[] = 0x80 | ($value & 0x7f);
            $value = $value >> 7;
        }
        $values[count($values)-1] &= 0x7f;

        $bytes = implode('', array_map('chr', $values));
        //$bytes = call_user_func_array('pack', array_merge(array('C*'), $values));
        $this->write($bytes);
    }

    public function sFixed32($value)
    {
        $bytes = pack('l*', $value);
        if ($this->isBigEndian()) {
            $bytes = strrev($bytes);
        }

        $this->write($bytes, 4);
    }

    public function fixed32($value)
    {
        $bytes = pack('N*', $value);
        $this->write($bytes, 4);
    }

    public function sFixed64($value)
    {
        $bytes = pack('V*', $value & 0xffffffff, $value / (0xffffffff+1));
        $this->write($bytes, 8);
    }

    public function fixed64($value)
    {
        return $this->sFixed64($value);
    }

    public function float($value)
    {
        $bytes = pack('f*', $value);
        if ($this->isBigEndian()) {
            $bytes = strrev($bytes);
        }
        $this->write($bytes, 4);
    }

    public function double($value)
    {
        $bytes = pack('d*', $value);
        if ($this->isBigEndian()) {
            $bytes = strrev($bytes);
        }
        $this->write($bytes, 8);
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
