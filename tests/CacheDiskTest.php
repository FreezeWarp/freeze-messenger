<?php
use PHPUnit\Framework\TestCase;

class CacheDiskTest extends TestCase
{
    /**
     * @var \Cache\CacheDisk
     */
    private static $cache;

    public static function setUpBeforeClass() {
        self::$cache = new \Cache\CacheDisk([]);
    }

    /**
     * Tests whether a value can be set and retrieved. Tests several value types.
     */
    public function testGetSet()
    {
        $this->assertTrue(self::$cache->set("diskTestInt", 1));
        $this->assertSame(1, self::$cache->get("diskTestInt"));

        $this->assertTrue(self::$cache->set("diskTestString", "hello"));
        $this->assertSame("hello", self::$cache->get("diskTestString"));

        $this->assertTrue(self::$cache->set("diskTestArray", "how are you?"));
        $this->assertSame("how are you?", self::$cache->get("diskTestArray"));

        $object = new stdClass();
        $object->property = 'Here we go';

        $this->assertTrue(self::$cache->set("diskTestObject", $object));

        $this->assertSame(var_export($object, true), var_export(self::$cache->get("diskTestObject"), true));

        $object->property = 'Here we go again.';
        $this->assertNotEquals($object, self::$cache->get("diskTestObject"));

        self::$cache->clear('diskTestInt');
        self::$cache->clear('diskTestString');
        self::$cache->clear('diskTestArray');
        self::$cache->clear('diskTestObject');
    }

    /**
     * Tests whether a value can be overwritten with set.
     */
    public function testGetSetOverwrite()
    {
        for ($i = 0; $i < 100; $i++) {
            $this->assertTrue(self::$cache->set("diskTestOverwrite", $i));
            $this->assertSame($i, self::$cache->get("diskTestOverwrite"));
        }

        self::$cache->clear('diskTestOverwrite');
    }

    /**
     * Tests whether a value can't be overwritten with add.
     */
    public function testAdd()
    {
        $this->assertTrue(self::$cache->add("diskTestAdd", "added"));
        $this->assertSame("added", self::$cache->get("diskTestAdd"));

        $this->assertFalse(self::$cache->add("diskTestAdd", "added again"));
        $this->assertSame("added", self::$cache->get("diskTestAdd"));

        $this->assertTrue(self::$cache->set("diskTestAdd", "set"));
        $this->assertSame("set", self::$cache->get("diskTestAdd"));

        self::$cache->clear('diskTestAdd');
    }

    /**
     * Tests whether a value isn't gettable after deletion.
     */
    public function testclear() {
        $this->assertTrue(self::$cache->set("diskTestDelete", "not deleted"));
        $this->assertSame("not deleted", self::$cache->get("diskTestDelete"));

        $this->assertTrue(self::$cache->clear("diskTestDelete"));
        $this->assertSame(false, self::$cache->get("diskTestDelete"));
    }
}
