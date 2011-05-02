<?php
/**
 * Cache_Redis Unit test script.
 *
 * Cache/Redis provides a fast, light and safe cache system.
 * It's replacement of Cache_Lite (file cache) to redis (memory cache).
 */

require_once '../src/Redis.php';

/**
 * Test class for Cache_Redis.
 */
class Cache_RedisTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Cache_Redis
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new Cache_Redis;
        $this->object->clean();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }

    /**
     * @todo Implement testGet().
     */
    public function testGet()
    {
        $this->assertEquals('', $this->object->get(''));

        $this->object->save('test get data', 'TEST GET');
        $this->assertEquals('test get data', $this->object->get('TEST GET'));
    }

    /**
     * @todo Implement testSave().
     */
    public function testSave()
    {
        $this->assertTrue(
            $this->object->save('test set data', 'TEST SET')
        );
    }

    /**
     * @todo Implement testRemove().
     */
    public function testRemove()
    {
        $this->object->save('test remove data', 'TEST REMOVE');
        $this->assertEquals(
            1, $this->object->remove('TEST REMOVE')
        );
    }

    /**
     * @todo Implement testExists().
     */
    public function testExists()
    {
        $this->object->save('test exists data', 'TEST EXISTS');
        $this->assertTrue(
            $this->object->exists('TEST EXISTS')
        );
    }
}
?>
