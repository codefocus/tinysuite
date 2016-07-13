<?php

namespace Codefocus\TinySuite;

use ArrayAccess;
use Exception;
use Countable;

abstract class AbstractArray implements ArrayAccess, Countable
{
    const ITEM_TYPE_UINT8  = 1;
    const ITEM_TYPE_UINT16 = 2;
    const ITEM_TYPE_UINT32 = 4;
    const ITEM_TYPE_UINT64 = 8;

    protected $memoryStream;
    protected $numItems = 0;
    protected $itemType;
    protected $packFormat;

    public function __construct($itemType)
    {
        //  Validate item type
        $this->itemType = $itemType;
        switch($this->itemType) {
        case self::ITEM_TYPE_UINT8:
            $this->packFormat = 'C';
            break;
        case self::ITEM_TYPE_UINT16:
            $this->packFormat = 'S';
            break;
        case self::ITEM_TYPE_UINT32:
            $this->packFormat = 'L';
            break;
        case self::ITEM_TYPE_UINT64:
            $this->packFormat = 'Q';
            break;
        default:
            throw new Exception('Invalid item type specified.');
        }

        $this->memoryStream = fopen('php://memory', 'br+');
    }

    /**
     * Format an item
     *
     * @param int $item
     *
     * @return int
     */
    protected function formatItem($item) {
        return pack($this->packFormat, $item);
    }

    /**
     * Seek to the end of the stream.
     *
     * @return void
     */
    abstract protected function seekToEnd();

    /**
     * Seek to the specified offset.
     *
     * @param int $offset
     *
     * @return void
     */
    abstract protected function seekToOffset($offset);

    /**
     * Seek to the specified byte position.
     *
     * @param int $cursor
     *
     * @return void
     */
    protected function seekToCursor($cursor) {
        fseek($this->memoryStream, $cursor);
    }

    /**
     * Append an item to the array.
     *
     * @param int $item
     *
     * @return void
     */
    abstract public function add($item);

    /**
     * Return whether the specified offset exists.
     *
     * @param int $offset
     *
     * @return boolean
     */
    abstract public function offsetExists($offset);

    /**
     * Return the item at the specified offset.
     *
     * @param int $offset
     *
     * @return int
     */
    abstract public function offsetGet($offset);

    /**
     * Replace the item at the specified offset.
     *
     * @param int $offset
     * @param int $item
     *
     * @return void
     */
    abstract public function offsetSet($offset, $item);

    /**
     * Unset the item at the specified offset.
     * Unsupported in WormArray.
     *
     * @param int $offset
     *
     * @return void
     */
    abstract public function offsetUnset($offset);

    /**
     * Return the number of items in the array.
     *
     * @return int
     */
    public function count() {
        return $this->numItems;
    }


    public function dumpAsHex($bytes = 64) {
        $this->seekToCursor(0);
        $data = fread($this->memoryStream, $bytes);
        $data = implode('', unpack('H*', $data));
        echo PHP_EOL.'-----'.PHP_EOL;
        echo chunk_split($data, 2, ' ') . 'EOF';
    }

}    //	class AbstractArray
