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

    protected function writeUINT8($value) {
        $this->memoryStreamSize += 1;
        fwrite($this->memoryStream, pack('C', $value));
    }

    protected function writeUINT16($value) {
        $this->memoryStreamSize += 2;
        fwrite($this->memoryStream, pack('I', $value));
    }

    protected function writeUINT32($value) {
        $this->memoryStreamSize += 4;
        fwrite($this->memoryStream, pack('L', $value));
    }

    protected function writeUINT64($value) {
        $this->memoryStreamSize += 8;
        fwrite($this->memoryStream, pack('Q', $value));
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

        // //  @TODO @DEBUG @REMOVEME
        // fseek($this->memoryStream, 1);
        // fwrite($this->memoryStream, pack('C', ord('t')));
        // fwrite($this->memoryStream, pack('L', 6));
        //
        // fwrite($this->memoryStream, pack('C', self::MARKER_LOOKUP_TABLE | self::MARKER_SIZE_16));
        // fwrite($this->memoryStream, pack('C', ord('e')));
        // fwrite($this->memoryStream, pack('L', 12));
        //
        // fwrite($this->memoryStream, pack('C', self::MARKER_VALUE | self::MARKER_SIZE_4));
        // fwrite($this->memoryStream, pack('L', 1234));
        // fwrite($this->memoryStream, pack('C', self::MARKER_VALUE | self::MARKER_SIZE_4));
        // fwrite($this->memoryStream, pack('L', 5678));

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
        fseek($this->memoryStream, $this->memoryStreamSize);
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
    protected function getCursorForKey($offset, $cursor = 0) {
        $offsetLength = strlen($offset);
        for ($iChar = 0; $iChar < $offsetLength; ++$iChar) {
            $char = ord($offset[$iChar]);
            $this->seekToCursor($cursor);
            //  Verify that this is a lookup table
            $marker = ord(fread($this->memoryStream, 1));
            if ($this->isValue($marker)) {
                //  Found our key.
                //  Return the address of its value.
                return $cursor + 1;
            }
            if (!$this->isLookupTable($marker)) {
                throw new Exception('Data corrupted at address ' . $cursor);
            }
            //  Find this character in the lookup table at the cursor.
            $lookupTableSize = $this->getSize($marker);
            $nextCursor = $this->lookupAddressForChar($char, $lookupTableSize);
            if (false === $nextCursor) {
                //  Character not found.
                //  This key is not in our hash table.
                return false;
            }
            else {
                //  Move the cursor to the address we found in the lookup table.
                $cursor = $nextCursor;
            }
        }

        return false;
    }

    /**
     * Return the address value stored for the specified char
     * in the lookup table at the current active stream cursor.
     * Returns false if not found.
     *
     * @param char $char
     * @param int $lookupTableSize
     *
     * @return int|false
     */
    protected function lookupAddressForChar($char, $lookupTableSize) {

        for ($iLookupTableEntry = 0; $iLookupTableEntry < $lookupTableSize; ++$iLookupTableEntry) {
            $cursor = $iLookupTableEntry * 5;
            $lookupTableEntry = unpack('Cchar/Iaddress', fread($this->memoryStream, 5));
            if (0 === $lookupTableEntry['char']) {
                //  null byte found, indicating end of lookup table.
                return false;
            }
            if ($lookupTableEntry['char'] === $char) {
                return $lookupTableEntry['address'];
            }
        }
        return false;
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

        echo PHP_EOL . 'Offset: ' . $offset . PHP_EOL;

        $cursor = $this->getCursorForKey($offset);
        if (false === $cursor) {
            //  Create key.

            $this->seekToEnd();
            
            $streamMetaData = stream_get_meta_data($this->memoryStream);
            var_dump($streamMetaData);
            throw new Exception('@TODO: Create key');
        }
        fseek($this->memoryStream, $cursor);
        //fwrite($this->memoryStream, pack('C', self::MARKER_VALUE | self::MARKER_SIZE_4));
        fwrite($this->memoryStream, pack('L', $item));
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
