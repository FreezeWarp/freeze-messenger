<?php

/**
 * Tests the core cache functionality of all five cache providers.
 */
class CacheTest extends PHPUnit\Framework\TestCase
{

    public static function cacheMethodProvider() {
        return [
            [new \Cache\Driver\Disk([])],
            [new \Cache\Driver\Apc()], // requires apc.enable_cli=1
            [new \Cache\Driver\Apcu()], // requires apcu.enable_cli=1
            [new \Cache\Driver\Redis([])],
            [new \Cache\Driver\Memcached()]
        ];
    }

    
    /**
     * Tests whether the cache instances report themselves to be available.
     * @dataProvider cacheMethodProvider
     */
    public function testAvailable(\Cache\DriverInterface $cache) {
        $this->assertTrue($cache::available());
    }

    /**
     * Tests whether a value can be set and retrieved. Tests several value types.
     * @dataProvider cacheMethodProvider
     */
    public function testGetSet(\Cache\DriverInterface $cache)
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

        $cache->delete('diskTestInt');
        $cache->delete('diskTestString');
        $cache->delete('diskTestArray');
        $cache->delete('diskTestObject');
    }

    /**
     * Tests whether a value can be overwritten with set.
     * @dataProvider cacheMethodProvider
     */
    public function testGetSetOverwrite(\Cache\DriverInterface $cache)
    {
        for ($i = 0; $i < 100; $i++) {
            $this->assertTrue($cache->set("diskTestOverwrite", $i));
            $this->assertSame($i, $cache->get("diskTestOverwrite"));
        }

        $cache->delete('diskTestOverwrite');
    }

    /**
     * Tests whether a value can't be overwritten with add.
     * @dataProvider cacheMethodProvider
     */
    public function testAdd(\Cache\DriverInterface $cache)
    {
        $this->assertTrue($cache->add("diskTestAdd", "added"));
        $this->assertSame("added", $cache->get("diskTestAdd"));

        $this->assertFalse($cache->add("diskTestAdd", "added again"));
        $this->assertSame("added", $cache->get("diskTestAdd"));

        $this->assertTrue($cache->set("diskTestAdd", "set"));
        $this->assertSame("set", $cache->get("diskTestAdd"));

        $cache->delete('diskTestAdd');
    }

    /**
     * Tests whether a value is correctly incremented.
     * @dataProvider cacheMethodProvider
     */
    public function testInc(\Cache\DriverInterface $cache) {
        foreach ([0, 1, 10, 100, 500, 23421] AS $i) {
            $this->assertTrue($cache->set("diskTestInc$i", $i));

            for ($j = $i, $k = 0; $j < $i + 10; $j += $k, $k++) {
                $this->assertTrue($cache->inc("diskTestInc$i", $k));
                $this->assertSame($j + $k, $cache->get("diskTestInc$i"));
            }
        }
    }

    /**
     * Tests whether a value isn't gettable after deletion.
     * @dataProvider cacheMethodProvider
     */
    public function testClear(\Cache\DriverInterface $cache) {
        $this->assertTrue($cache->set("diskTestDelete", "not deleted"));
        $this->assertSame("not deleted", $cache->get("diskTestDelete"));

        $this->assertTrue($cache->delete("diskTestDelete"));
        $this->assertSame(false, $cache->get("diskTestDelete"));
    }

    /**
     * Tests whether a value isn't gettable after clearAll.
     * @dataProvider cacheMethodProvider
     */
    public function testClearAll(\Cache\DriverInterface $cache) {
        $this->assertTrue($cache->set("diskTestClearAll1", "not deleted"));
        $this->assertTrue($cache->set("diskTestClearAll2", "not deleted"));
        $this->assertTrue($cache->set("diskTestClearAll3", "not deleted"));

        $this->assertTrue($cache->deleteAll());

        $this->assertSame(false, $cache->get("diskTestClearAll1"));
        $this->assertSame(false, $cache->get("diskTestClearAll2"));
        $this->assertSame(false, $cache->get("diskTestClearAll3"));
    }
}
