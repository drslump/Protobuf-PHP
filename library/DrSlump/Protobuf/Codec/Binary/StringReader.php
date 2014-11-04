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
class StringReader extends Reader
{
    /** @var offset */
    protected $_offset;
    
    /**
     * Create a new reader from a file descriptor or a string of bytes
     *
     * @param string $input
     */
    public function __construct($input = NULL)
    {
        if (NULL !== $input) {
            $this->init($input);
        }
    }

    public function __destruct()
    {
        $this->_input = NULL;
    }
    
    /**
     * Create a new reader from a string of bytes
     *
     * @param resource|string $fdOrString
     */
    public function init($input)
    {
        $this->_offset = 0;
        return parent::init($input);
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

        $bytes = substr($this->_input, $this->_offset, $length);
        
        if (strlen($bytes) !== $length) {
            throw new \RuntimeException('Failed to read ' . $length . ' bytes');
        }

        $this->_offset += $length;
        
        return $bytes;
    }

    /**
     * Check if we have reached the end of the stream
     *
     * @return bool
     */
    public function eof()
    {
        return $this->_offset >= strlen($this->_input);
    }

    /**
     * Obtain the current position in the stream
     *
     * @return int
     */
    public function pos()
    {
        return $this->_offset;
    }
}
