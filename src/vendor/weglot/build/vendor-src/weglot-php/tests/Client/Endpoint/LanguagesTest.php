<?php

namespace Weglot\Vendor\Weglot\Tests\Client\Endpoint;

use Weglot\Vendor\PHPUnit\Framework\TestCase;
use Weglot\Vendor\Weglot\Client\Api\LanguageCollection;
use Weglot\Vendor\Weglot\Client\Client;
use Weglot\Vendor\Weglot\Client\Endpoint\LanguagesList;
class LanguagesTest extends TestCase
{
    /**
     * @var Client
     */
    protected $client;
    /**
     * @var LanguageCollection
     */
    protected $languages;
    protected function setup(): void
    {
        $this->client = new Client($_ENV['WG_API_KEY'], 3, 1);
        $this->client->setOptions(['host' => 'https://api.weglot.dev']);
        $endpoint = new LanguagesList($this->client);
        $this->languages = $endpoint->handle();
    }
    /**
     * @return void
     */
    public function testCount()
    {
        $this->assertCount(140, $this->languages);
    }
    /**
     * @return void
     */
    public function testGetCode()
    {
        $this->assertEquals('Finnish', $this->languages->getCode('fi')->getEnglishName());
        $this->assertEquals('Hrvatski', $this->languages->getCode('hr')->getLocalName());
        $this->assertNull($this->languages->getCode('foo'));
    }
    /**
     * @return void
     */
    public function testSerialize()
    {
        $json = json_encode($this->languages->getCode('fa'));
        $expected = '{"internal_code":"fa","external_code":"fa","english":"Persian","local":"\u0641\u0627\u0631\u0633\u06cc","rtl":true}';
        $this->assertEquals($expected, $json);
        $json = json_encode($this->languages->getCode('fr'));
        $expected = '{"internal_code":"fr","external_code":"fr","english":"French","local":"Fran\u00e7ais","rtl":false}';
        $this->assertEquals($expected, $json);
        $json = json_encode($this->languages->getCode('ar'));
        $expected = '{"internal_code":"ar","external_code":"ar","english":"Arabic","local":"\u0627\u0644\u0639\u0631\u0628\u064a\u0629\u200f","rtl":true}';
        $this->assertEquals($expected, $json);
        $json = json_encode($this->languages->getCode('he'));
        $expected = '{"internal_code":"he","external_code":"he","english":"Hebrew","local":"\u05e2\u05d1\u05e8\u05d9\u05ea","rtl":true}';
        $this->assertEquals($expected, $json);
        $json = json_encode($this->languages->getCode('no'));
        $expected = '{"internal_code":"no","external_code":"no","english":"Norwegian","local":"Norsk","rtl":false}';
        $this->assertEquals($expected, $json);
    }
}
