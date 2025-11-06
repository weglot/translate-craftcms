<?php

namespace Weglot\Vendor\Weglot\Tests\Client\Endpoint;

use Weglot\Vendor\PHPUnit\Framework\TestCase;
use Weglot\Vendor\Symfony\Component\Cache\Adapter\ArrayAdapter;
use Weglot\Vendor\Weglot\Client\Api\Enum\BotType;
use Weglot\Vendor\Weglot\Client\Api\Enum\WordType;
use Weglot\Vendor\Weglot\Client\Api\TranslateEntry;
use Weglot\Vendor\Weglot\Client\Api\WordCollection;
use Weglot\Vendor\Weglot\Client\Api\WordEntry;
use Weglot\Vendor\Weglot\Client\Client;
use Weglot\Vendor\Weglot\Client\Endpoint\Translate;
class CachedTranslateTest extends TestCase
{
    /**
     * @var Client
     */
    protected $client;
    /**
     * @var TranslateEntry
     */
    protected $entry;
    /**
     * @var Translate
     */
    protected $translate;
    protected function setup(): void
    {
        // Client
        $this->client = new Client($_ENV['WG_API_KEY'], 3, 1);
        $this->client->setOptions(['host' => 'https://api.weglot.dev']);
        $headerKey = $_ENV['WEGLOT_WAF_HEADER_KEY'];
        $headerValue = $_ENV['WEGLOT_WAF_HEADER_VALUE'];
        if ($headerKey && $headerValue) {
            $this->client->getHttpClient()->addHeader("{$headerKey}: {$headerValue}");
        }
        // PSR-6 CacheItemPool
        $itemPool = new ArrayAdapter();
        $this->client->setCacheItemPool($itemPool);
        // TranslateEntry
        $params = ['language_from' => 'en', 'language_to' => 'de', 'title' => 'Weglot | Translate your website - Multilingual for WordPress, Shopify, ...', 'request_url' => 'https://weglot.com/', 'bot' => BotType::HUMAN];
        $this->entry = new TranslateEntry($params);
        $this->entry->getInputWords()->addOne(new WordEntry('This is a blue car', WordType::TEXT))->addOne(new WordEntry('This is a black car', WordType::TEXT));
        // Translate endpoint
        $this->translate = new Translate($this->entry, $this->client);
    }
    /**
     * @return void
     */
    public function testSetOutputWords()
    {
        $this->entry->setOutputWords();
        $this->assertInstanceOf(WordCollection::class, $this->entry->getOutputWords());
        $this->assertCount(0, $this->entry->getOutputWords());
    }
    /**
     * @return void
     */
    public function testGetParams()
    {
        $params = $this->entry->getParams();
        $this->assertEquals('en', $params['language_from']);
        $this->assertEquals('de', $params['language_to']);
        $this->assertEquals('https://weglot.com/', $params['request_url']);
        $this->assertEquals(BotType::HUMAN, $params['bot']);
    }
    /**
     * @return void
     */
    public function testEndpointCountWord()
    {
        $translated = $this->translate->handle();
        $this->assertCount($this->entry->getInputWords()->count(), $translated->getOutputWords());
    }
    /**
     * @return void
     */
    public function testTranslateEntry()
    {
        $this->assertInstanceOf(TranslateEntry::class, $this->translate->getTranslateEntry());
        $this->assertSame($this->translate->getTranslateEntry(), $this->entry);
    }
    /**
     * @return void
     */
    public function testPath()
    {
        $this->assertEquals('/translate', $this->translate->getPath());
    }
    /**
     * @return void
     */
    public function testCachedRequest()
    {
        $translated = $this->translate->handle();
        $this->assertCount($this->entry->getInputWords()->count(), $translated->getOutputWords());
        $translated = $this->translate->handle();
        $this->assertCount($this->entry->getInputWords()->count(), $translated->getOutputWords());
    }
}
