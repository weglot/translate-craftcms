<?php

namespace Weglot\Vendor\Weglot\Tests\Parser\Check\Dom;

use Weglot\Vendor\PHPUnit\Framework\TestCase;
use Weglot\Vendor\Weglot\Client\Api\Enum\BotType;
use Weglot\Vendor\Weglot\Client\Client;
use Weglot\Vendor\Weglot\Parser\ConfigProvider\ManualConfigProvider;
use Weglot\Vendor\Weglot\Parser\Parser;
class MetaContentTest extends TestCase
{
    /**
     * @var string
     */
    protected $url;
    /**
     * @var ManualConfigProvider
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
    /**
     * @var string
     */
    protected $content = '<html><head><title>MyWebPage</title><meta name="description" content="This is my first web page, be kind :)" /><meta</head><body>Coucou</body></html>';
    protected function setup(): void
    {
        $this->url = 'https://foo.bar/baz';
        // Config manually
        $this->config = new ManualConfigProvider($this->url, BotType::HUMAN);
        // Client
        $this->client = new Client($_ENV['WG_API_KEY'], 3);
        $this->client->setOptions(['host' => 'https://api.weglot.dev']);
        $this->markTestSkipped('TODO');
    }
    /**
     * @return void
     */
    public function testCheck()
    {
        // Parser
        $this->parser = new Parser($this->client, $this->config);
        // Run the Parser
        $translatedContent = $this->parser->translate($this->content, 'en', 'de');
        $old = $this->_getSimpleDom($this->content);
        $new = $this->_getSimpleDom($translatedContent);
        $oldContent = $old->find('meta[name="description"]', 0)->content;
        $newContent = $new->find('meta[name="description"]', 0)->content;
        $this->assertEquals('This is my first web page, be kind :)', $oldContent);
        $this->assertNotEquals($oldContent, $newContent);
    }
    /**
     * @param string $source
     *
     * @return \WGSimpleHtmlDom\simple_html_dom
     */
    private function _getSimpleDom($source)
    {
        return \Weglot\Vendor\WGSimpleHtmlDom\str_get_html($source, \true, \true, WG_DEFAULT_TARGET_CHARSET, \false, WG_DEFAULT_BR_TEXT, WG_DEFAULT_SPAN_TEXT);
    }
}
