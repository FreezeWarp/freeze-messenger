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

    public static function cacheMethodProvider() {
        return [
            [new \Cache\CacheDisk([])],
            [new \Cache\CacheApc()], // requires apc.enable_cli=1
            [new \Cache\CacheApcu()], // requires apcu.enable_cli=1
            [new \Cache\CacheRedis([])],
            [new \Cache\CacheMemcached()],
        ];
    }

    /**
     * Tests whether a value can be set and retrieved. Tests several value types.
     * @dataProvider cacheMethodProvider
     */

    public function testGetSet($cache)
    {
        $this->assertTrue($cache->set("diskTestInt", 1));
        $this->assertSame(1, $cache->get("diskTestInt"));

        $this->assertTrue($cache->set("diskTestString", "hello"));
        $this->assertSame("hello", $cache->get("diskTestString"));

        $this->assertTrue($cache->set("diskTestArray", "how are you?"));
        $this->assertSame("how are you?", $cache->get("diskTestArray"));

        $object = new stdClass();
        $object->property = 'Here we go';

        $this->assertTrue($cache->set("diskTestObject", $object));

        $this->assertSame(var_export($object, true), var_export($cache->get("diskTestObject"), true));

        $object->property = 'Here we go again.';
        $this->assertNotEquals($object, $cache->get("diskTestObject"));

        $cache->clear('diskTestInt');
        $cache->clear('diskTestString');
        $cache->clear('diskTestArray');
        $cache->clear('diskTestObject');
    }

    /**
     * Tests whether a value can be overwritten with set.
     * @dataProvider cacheMethodProvider
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
     * @dataProvider cacheMethodProvider
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
     * @dataProvider cacheMethodProvider
     */
    public function testClear() {
        $this->assertTrue(self::$cache->set("diskTestDelete", "not deleted"));
        $this->assertSame("not deleted", self::$cache->get("diskTestDelete"));

        $this->assertTrue(self::$cache->clear("diskTestDelete"));
        $this->assertSame(false, self::$cache->get("diskTestDelete"));
    }

    /**
     * Tests whether a value isn't gettable after clearAll.
     * @dataProvider cacheMethodProvider
     */
    public function testClearAll() {
        $this->assertTrue(self::$cache->set("diskTestClearAll1", "not deleted"));
        $this->assertTrue(self::$cache->set("diskTestClearAll2", "not deleted"));
        $this->assertTrue(self::$cache->set("diskTestClearAll3", "not deleted"));

        $this->assertTrue(self::$cache->clearAll());

        $this->assertSame(false, self::$cache->get("diskTestClearAll1"));
        $this->assertSame(false, self::$cache->get("diskTestClearAll2"));
        $this->assertSame(false, self::$cache->get("diskTestClearAll3"));
    }
}
