<?php

use PHPUnit\Framework\TestCase;

use Codefocus\TinySuite\HashTable;

class HashTableTest extends TestCase
{
    public function testAppend()
    {
        $hashTable = new HashTable(HashTable::ITEM_TYPE_UINT32);

        $hashTable->dumpAsHex();

        $hashTable["test"] = 49;



        // //  Test that the count is accurate.
        // $this->assertEquals(3, count($wormArray));
        //
        // //  Test that each item was added correctly.
        // $this->assertEquals(101, $wormArray[0]);
        // $this->assertEquals(103, $wormArray[2]);
        // $this->assertEquals(102, $wormArray[1]);
        //
        // //  Test that the data was not modified.
        // $this->assertEquals(101, $wormArray[0]);

    }
}
