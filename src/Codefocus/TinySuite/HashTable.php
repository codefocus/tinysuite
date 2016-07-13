<?php

namespace Codefocus\TinySuite;

use ArrayAccess;
use Exception;
use Countable;

/**
 * Memory-efficient Array replacement for storing
 * unsigned 8, 16, 32 or 64 bit integers.
 */
class HashTable extends AbstractArray implements ArrayAccess, Countable
{
    const MARKER_LOOKUP_TABLE   = 0x01;
    const MARKER_VALUE          = 0x02;
    const MARKER_JUMP_TO        = 0x03;

    const MARKER_SIZE_1         = 0x00;
    const MARKER_SIZE_2         = 0x10;
    const MARKER_SIZE_4         = 0x20;
    const MARKER_SIZE_8         = 0x30;
    const MARKER_SIZE_16        = 0x40;
    const MARKER_SIZE_32        = 0x50;
    const MARKER_SIZE_64        = 0x60;
    const MARKER_SIZE_128       = 0x70;
    const MARKER_SIZE_256       = 0x80;

    const LOOKUP_TABLE_INITIAL_SIZE = 16;

    protected $packFormat;

    public function __construct($itemType)
    {
        //  Let the parent class construct the memory stream.
        parent::__construct($itemType);
        //  Initialize initial lookup table.
        $this->initializeLookupTable(0);
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
        throw new Exception('@TODO');
        fwrite($this->memoryStream, $this->formatItem($item));
        $this->numItems++;
    }

    protected function initializeLookupTable($byteOffset) {
        fseek($this->memoryStream, $byteOffset);
        //  Mark this address as a lookup table.
        $lookupTableMarker = self::MARKER_LOOKUP_TABLE | self::MARKER_SIZE_16;
        $lookupTableSize = $this->getSize($lookupTableMarker);
        fwrite($this->memoryStream, pack('C', $lookupTableMarker));
        for ($iKeyChar = 0; $iKeyChar < $lookupTableSize; ++$iKeyChar) {
            //  Initialize this position with an empty character,
            //  followed by an empty 4-byte address.
            fwrite($this->memoryStream, pack('C', 0));
            fwrite($this->memoryStream, pack('L', 0));
        }
    }


    protected function isLookupTable($marker) {
        return (($marker & self::MARKER_LOOKUP_TABLE) === self::MARKER_LOOKUP_TABLE);
    }

    protected function isValue($marker) {
        return (($marker & self::MARKER_VALUE) === self::MARKER_VALUE);
    }




    protected function getSize($marker) {
        return pow(2, ($marker >> 4));
    }




    /**
     * Seek to the end of the stream.
     *
     * @return void
     */
    protected function seekToEnd() {
        throw new Exception('@TODO');
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
        throw new Exception('@TODO');
        ///fseek($this->memoryStream, $offset) * $...;
    }

    /**
     * Append an item to the array.
     *
     * @param int $item
     *
     * @return void
     */
    public function add($item) {
        throw new Exception('@TODO');
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
        throw new Exception('@TODO');
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
        throw new Exception('@TODO');
        if (!$this->offsetExists($offset)) {
            throw new Exception('Invalid offset: ' . $offset);
        }
        $this->seekToOffset($offset);
        list(,$return) = unpack($this->packFormat, fread($this->memoryStream, $this->itemType));
        return $return;
    }

    /**
     * Return the byte position for the value of the specified offset (key).
     *
     * @param string $offset
     *
     * @return int
     */
    protected function getCursorForKey($offset) {
        $cursor = 0;
        $offsetLength = strlen($offset);


        for ($iChar = 0; $iChar < $offsetLength; ++$iChar) {
            $tableCursor = $cursor;
            $this->seekToCursor($tableCursor);
            //  Verify that this is a lookup table
            $marker = ord(fread($this->memoryStream, 1));//pack('C', fread($this->memoryStream, 1));
            if ($this->isValue($marker)) {
                //  Found our value.
                echo PHP_EOL . 'VALUE FOUND AT:' . $tableCursor;
                return $tableCursor;
            }
            if (!$this->isLookupTable($marker)) {
                throw new Exception('Data corruption at address ' . $tableCursor);
            }
            //  Read the lookup table.
            //  [ [char][addr] ] * {marker.length}
            $lookupTableSize = $this->getSize($marker);
            $lookupTable = fread($this->memoryStream, $lookupTableSize * 5);

            for ($iLookupTableEntry = 0; $iLookupTableEntry < $lookupTableSize; ++$iLookupTableEntry) {
                if (0 === ord($lookupTable[ $iLookupTableEntry * 5 ])) {
                    //  null byte found, indicating end of lookup table.
                    echo PHP_EOL . 'NOTHING FOUND. LOOKUP TABLE ENDS AT ' . $tableCursor;
                    return false;
                }
            }



            print_r('--'.strlen($lookupTable).'--');


            // for ($iKeyChar = 0; $iKeyChar < $lookupTableSize; ++$iKeyChar) {
            //     //  Initialize this position with an empty character,
            //     //  followed by an empty 4-byte address.
            //     fwrite($this->memoryStream, pack('C', 0));
            //     fwrite($this->memoryStream, pack('L', 0));
            // }



            echo PHP_EOL . 'marker:' . dechex($marker);


            //  Read the lookup table length


            //$cursor
        }


    }


    /**
     * Replace the item at the specified offset.
     *
     * @param string $offset
     * @param int $item
     *
     * @return void
     */
    public function offsetSet($offset, $item) {

        echo PHP_EOL . 'Offset: ' . $offset;

        $byteOffset = $this->getCursorForKey($offset);
        //  1.  Traverse the lookup table(s) to see if this key exists.


        return 0;
        // if ($this->tableHasKey($offset)) {
        //
        // }




        throw new Exception('@TODO');
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
        throw new Exception('@TODO');
        throw new Exception('unset() is unsupported in WormArray');
    }

}    //	class HashTable
