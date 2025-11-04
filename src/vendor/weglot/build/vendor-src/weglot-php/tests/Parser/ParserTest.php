<?php

namespace Weglot\Vendor\Weglot\Tests\Parser;

use Weglot\Vendor\PHPUnit\Framework\TestCase;
use Weglot\Vendor\Weglot\Client\Api\Enum\BotType;
use Weglot\Vendor\Weglot\Client\Client;
use Weglot\Vendor\Weglot\Parser\ConfigProvider\ManualConfigProvider;
use Weglot\Vendor\Weglot\Parser\ConfigProvider\ServerConfigProvider;
use Weglot\Vendor\Weglot\Parser\Parser;
class ParserTest extends TestCase
{
    /**
     * @var string
     */
    protected $url;
    /**
     * @var array
     */
    protected $config;
    /**
     * @var Client
     */
    protected $client;
    /**
     * @var Parser
     */
    protected $parser;
    protected function setup(): void
    {
        $this->url = 'https://weglot.com/documentation/getting-started';
        // Config with $_SERVER variables
        $_SERVER['SERVER_NAME'] = 'weglot.com';
        $_SERVER['REQUEST_URI'] = '/documentation/getting-started';
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['SERVER_PROTOCOL'] = 'http//';
        $_SERVER['SERVER_PORT'] = 443;
        $_SERVER['HTTP_USER_AGENT'] = 'Google';
        // Config manually
        $this->config = ['manual' => new ManualConfigProvider($this->url, BotType::HUMAN), 'server' => new ServerConfigProvider()];
        // Client
        $this->client = new Client($_ENV['WG_API_KEY'], 1, 1);
        $this->client->setOptions(['host' => 'https://api.weglot.dev']);
    }
    /**
     * @return void
     */
    public function testTranslateManual()
    {
        // Parser
        $this->parser = new Parser($this->client, $this->config['manual']);
        // Run the Parser
        $translatedContent = $this->parser->translate($this->_getContent($this->url), 'en', 'de');
        $this->assertIsString($translatedContent);
    }
    /**
     * @return void
     */
    public function testTranslateServer()
    {
        // Parser
        $this->parser = new Parser($this->client, $this->config['server']);
        // Run the Parser
        $translatedContent = $this->parser->translate($this->_getContent($this->url), 'en', 'de');
        $this->assertTrue(\is_string($translatedContent));
    }
    /**
     * @return void
     */
    public function testParserEngine1NodeSplit()
    {
        $this->_parserEngineNodeSplit('cases-v1', 1);
    }
    /**
     * @return void
     */
    public function testParserEngine2NodeSplit()
    {
        $this->_parserEngineNodeSplit('cases-v2-php', 2);
    }
    /**
     * @return void
     */
    public function testParserEngine3NodeSplit()
    {
        $this->_parserEngineNodeSplit('cases-v3', 3);
    }
    /**
     * @param string $case
     * @param int    $version
     *
     * @return void
     */
    public function _parserEngineNodeSplit($case, $version)
    {
        $cases = $this->loadJSON($case);
        foreach ($cases as $test) {
            // Parser
            $client = new Client($_ENV['WG_API_KEY'], $version, 1);
            $this->parser = new Parser($client, $this->config['server']);
            // Run the Parser
            $parsed = $this->parser->parse($test['body']);
            $strings = $parsed['words'];
            foreach ($strings as $k => $string) {
                $this->assertEquals($test['expected'][$k]['w'], $string->getWord());
                $this->assertEquals($test['expected'][$k]['t'], $string->getType());
            }
            $this->assertEquals(\count($test['expected']), \count($strings));
        }
    }
    /**
     * @param string $key
     *
     * @return array
     */
    private function loadJSON($key)
    {
        $content = file_get_contents(__DIR__ . '/../../vendor/weglot/translation-definitions/data/cases/' . $key . '.json');
        $this->assertNotFalse($content);
        return json_decode($content, \true);
    }
    /**
     * @param string $url
     *
     * @return string
     */
    private function _getContent($url)
    {
        // Fetching url content
        $ch = curl_init($url);
        curl_setopt($ch, \CURLOPT_RETURNTRANSFER, \true);
        curl_setopt($ch, \CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
        $content = curl_exec($ch);
        curl_close($ch);
        $this->assertIsString($content);
        return $content;
    }
}
