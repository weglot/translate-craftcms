<?php

namespace Weglot\Vendor\Weglot\Tests\Util;

use Weglot\Vendor\PHPUnit\Framework\TestCase;
use Weglot\Vendor\Weglot\Client\Api\WordCollection;
use Weglot\Vendor\Weglot\Client\Api\WordEntry;
use Weglot\Vendor\Weglot\Util\JsonUtil;
class JsonLdTest extends TestCase
{
    /**
     * @var array
     */
    protected $json = [];
    protected function setup(): void
    {
        $raw = <<<EOT
{
  "@context": {
    "name": "http://xmlns.com/foaf/0.1/name",
    "homepage": {
      "@id": "http://xmlns.com/foaf/0.1/workplaceHomepage",
      "@type": "@id"
    },
    "Person": "http://xmlns.com/foaf/0.1/Person"
  },
  "@id": "http://me.example.com",
  "@type": "Person",
  "name": "John Smith",
  "homepage": "http://www.example.com/"
}
EOT;
        $this->json = json_decode($raw, \true);
    }
    /**
     * @return void
     */
    public function testGet()
    {
        $this->assertNull(JsonUtil::get($this->json, 'description'));
        $this->assertEquals('John Smith', JsonUtil::get($this->json, 'name'));
    }
    /**
     * @return void
     */
    public function testAdd()
    {
        $words = new WordCollection();
        $words->addOne(new WordEntry('Une voiture bleue'));
        $this->assertCount(1, $words);
        $value = JsonUtil::get($this->json, 'name');
        JsonUtil::add($words, $value);
        $this->assertCount(2, $words);
        $this->assertEquals(new WordEntry($value), $words[1]);
    }
    /**
     * @return void
     */
    public function testSet()
    {
        $nextJson = 0;
        $words = new WordCollection();
        $words->addOne(new WordEntry('Une voiture bleue'));
        $this->assertEquals(0, $nextJson);
        $this->assertCount(1, $words);
        $data = JsonUtil::set($words, $this->json, 'name', $nextJson);
        $this->assertEquals(1, $nextJson);
        $this->assertEquals($data['name'], $words[0]->getWord());
    }
}
