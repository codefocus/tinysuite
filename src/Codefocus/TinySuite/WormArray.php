<?php

namespace Codefocus\Vernacular;

use ArrayAccess;
use Exception;
use Countable;

/**
 * Lightweight, blazing fast Array replacement
 * for storing unsigned 64-bit integers.
 */
class WormArray implements ArrayAccess, Countable
{
    const BYTES_UNSIGNED_LONG = 8;

    const ITEM_TYPE_UINT8  =  8;
    const ITEM_TYPE_UINT16 = 16;
    const ITEM_TYPE_UINT32 = 32;
    const ITEM_TYPE_UINT64 = 64;

    protected $memoryStream;
    protected $numItems = 0;
    protected $itemType;

    public function __construct($itemType)
    {

        //  @TODO:  Validate $itemType
        $this->itemType = $itemType;


        $this->memoryStream = fopen('php://memory', 'br+');
    }

    /**
     * Append an item to the array.
     *
     * @param int $item
     *
     * @return void
     */
    public function add($item) {
        //  Append an 8 byte unsigned long to the memory stream.
        $this->seekToEnd();
        fwrite($this->memoryStream, pack('Q', $item));
        $this->numItems++;
    }

    /**
     * Add an item to the array, at the current position.
     * Similar to add(), but without the overhead of seeking
     * to the end of the stream.
     *
     * @param int $item
     *
     * @return void
     */
    public function addAtCurrentPosition($item) {
        fwrite($this->memoryStream, pack('Q', $item));
        $this->numItems++;
    }

    /**
     * Seek to the end of the stream.
     *
     * @return void
     */
    protected function seekToEnd() {
        fseek($this->memoryStream, $this->numItems * self::BYTES_UNSIGNED_LONG);
    }

    /**
     * Seek to the specified offset.
     *
     * @param int $offset
     *
     * @return void
     */
    protected function seekToOffset($offset) {
        fseek($this->memoryStream, $offset * self::BYTES_UNSIGNED_LONG);
    }

    /**
     * Return whether the specified offset exists.
     *
     * @param int $offset
     *
     * @return boolean
     */
    public function offsetExists($offset) {
        return (
            is_int($offset)
         && $offset <= $this->numItems
         && $offset >= 0
        );
    }

    /**
     * Return the item at the specified offset.
     *
     * @param int $offset
     *
     * @return int
     */
    public function offsetGet($offset) {
        if (!$this->offsetExists($offset)) {
            throw new Exception('Invalid offset: ' . $offset);
        }
        $this->seekToOffset($offset);
        list(,$return) = unpack('Q', fread($this->memoryStream, self::BYTES_UNSIGNED_LONG));
        return $return;
    }

    /**
     * Replace the item at the specified offset.
     *
     * @param int $offset
     * @param int $item
     *
     * @return void
     */
    public function offsetSet($offset, $item) {
        if (null === $offset) {
            return $this->add($item);
        }
        if (!$this->offsetExists($offset)) {
            throw new Exception('Invalid offset: ' . $offset);
        }
        $this->seekToOffset($offset);
        fwrite($this->memoryStream, pack('Q', $item));
    }

    /**
     * Unset the item at the specified offset.
     * Unsupported in WormArray.
     *
     * @param int $offset
     *
     * @return void
     */
    public function offsetUnset($offset) {
        throw new Exception('unset() is unsupported in WormArray');
    }

    /**
     * Return the number of items in the array.
     *
     * @return int
     */
    public function count() {
        return $this->numItems;
    }

}    //	class WormArray
