<?php

namespace Codefocus\TinySuite;

use ArrayAccess;
use Exception;
use Countable;

/**
 * Memory-efficient Array replacement for storing
 * unsigned 8, 16, 32 or 64 bit integers.
 */
class WormArray extends AbstractArray implements ArrayAccess, Countable
{
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
        fwrite($this->memoryStream, $this->formatItem($item));
        $this->numItems++;
    }

    /**
     * Seek to the end of the stream.
     *
     * @return void
     */
    protected function seekToEnd() {
        fseek($this->memoryStream, $this->numItems * $this->itemType);
    }

    /**
     * Seek to the specified offset.
     *
     * @param int $offset
     *
     * @return void
     */
    protected function seekToOffset($offset) {
        fseek($this->memoryStream, $offset * $this->itemType);
    }

    /**
     * Append an item to the array.
     *
     * @param int $item
     *
     * @return void
     */
    public function add($item) {
        //  Append an item to the memory stream.
        $this->seekToEnd();
        fwrite($this->memoryStream, $this->formatItem($item));
        $this->numItems++;
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
        list(,$return) = unpack($this->packFormat, fread($this->memoryStream, $this->itemType));
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
        fwrite($this->memoryStream, $this->formatItem($item));
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

}    //	class WormArray
