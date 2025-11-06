<?php

namespace Weglot\Vendor\Weglot\Tests\Client;

use Weglot\Vendor\PHPUnit\Framework\TestCase;
use Weglot\Vendor\Symfony\Component\Cache\Adapter\ArrayAdapter;
use Weglot\Vendor\Weglot\Client\Client;
class ClientCachingTest extends TestCase
{
    /**
     * @var Client
     */
    protected $client;
    protected function setUp(): void
    {
        $this->client = new Client($_ENV['WG_API_KEY'], 3, 1);
        $this->client->setOptions(['host' => 'https://api.weglot.dev']);
        $itemPool = new ArrayAdapter();
        $this->client->setCacheItemPool($itemPool);
    }
    /**
     * @return void
     */
    public function testExpire()
    {
        $this->assertEquals(604800, $this->client->getCache()->getExpire());
        $this->client->getCache()->setExpire(240);
        $this->assertEquals(240, $this->client->getCache()->getExpire());
    }
    /**
     * @return void
     */
    public function testGenerateKey()
    {
        $cacheKey = $this->client->getCache()->generateKey(['method' => 'GET', 'endpoint' => '/translate', 'content' => []]);
        $this->assertEquals('wg_8bdaed005c88bda03e938c3de08da157ecbe5dfa', $cacheKey);
    }
    /**
     * @return void
     */
    public function testGetItem()
    {
        $key = 'getItem';
        $item = $this->client->getCache()->get($key);
        $this->assertNull($item->get());
        $item->set('some value');
        $this->client->getCache()->save($item);
        $this->assertEquals('some value', $this->client->getCache()->get($key)->get());
    }
}
