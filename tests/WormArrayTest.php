<?php

use PHPUnit\Framework\TestCase;

use Codefocus\TinySuite\WormArray;

class WormArrayTest extends TestCase
{
    public function testAppend()
    {
        $wormArray = new WormArray(WormArray::ITEM_TYPE_UINT32);

        $wormArray[] = 101;
        $wormArray[] = 102;
        $wormArray[] = 65536;
        
        //  Test that the count is accurate.
        $this->assertEquals(3, count($wormArray));

        //  Test that each item was added correctly.
        $this->assertEquals(101, $wormArray[0]);
        $this->assertEquals(65536, $wormArray[2]);
        $this->assertEquals(102, $wormArray[1]);

        //  Test that the data was not modified.
        $this->assertEquals(101, $wormArray[0]);

    }
}
