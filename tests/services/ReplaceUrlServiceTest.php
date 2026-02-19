<?php

namespace weglot\craftweglot\tests\services;

use PHPUnit\Framework\TestCase;
use weglot\craftweglot\Plugin;
use weglot\craftweglot\services\ReplaceUrlService;

class ReplaceUrlServiceTest extends TestCase
{
    private ReplaceUrlService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ReplaceUrlService();
    }

    /**
     * Test that modifyLink returns the input unchanged when the pattern does not match any links.
     */
    public function testModifyLinkNoMatch(): void
    {
        $pattern = '/<a href="(https:\/\/example\.com)">/';
        $translatedPage = '<p>No links here</p>';
        $type = 'a';

        $result = $this->service->modifyLink($pattern, $translatedPage, $type);

        $this->assertSame($translatedPage, $result);
    }

    /**
     * Test modifyLink when the pattern matches links and processes them accordingly.
     */
    public function testModifyLinkWithMatch(): void
    {
        $pattern = '/<a(.*?)href=(["\'])(.*?)\2(.*?)>/';
        $translatedPage = '<a href="https://example.com">Example</a>';
        $type = 'a';

        // Mock Plugin ReplaceLinkService behavior
        $mockReplaceLinkService = $this->createMock(Plugin::getInstance()->getReplaceLinkService()::class);
        $mockReplaceLinkService->expects($this->once())
                               ->method('replaceA')
                               ->willReturn('<a href="https://example-translated.com">Example</a>');

        Plugin::getInstance()->method('getReplaceLinkService')->willReturn($mockReplaceLinkService);

        $result = $this->service->modifyLink($pattern, $translatedPage, $type);

        $this->assertSame('<a href="https://example-translated.com">Example</a>', $result);
    }

    /**
     * Test modifyLink skips processing for overly long URLs.
     */
    public function testModifyLinkSkipsLongUrls(): void
    {
        $pattern = '/<a(.*?)href=(["\'])(.*?)\2(.*?)>/';
        $longUrl = str_repeat('a', 1500);
        $translatedPage = "<a href=\"$longUrl\">Example</a>";
        $type = 'a';

        $result = $this->service->modifyLink($pattern, $translatedPage, $type);

        $this->assertSame($translatedPage, $result);
    }

    /**
     * Test modifyLink skips processing for Craft CMS action URLs.
     */
    public function testModifyLinkSkipsActionUrls(): void
    {
        $pattern = '/<a(.*?)href=(["\'])(.*?)\2(.*?)>/';
        $translatedPage = '<a href="/index.php/actions/some-action">Action</a>';
        $type = 'a';

        $result = $this->service->modifyLink($pattern, $translatedPage, $type);

        $this->assertSame($translatedPage, $result);
    }

    /**
     * Test modifyLink uses the appropriate ReplaceLinkService method for 'form' type.
     */
    public function testModifyLinkFormType(): void
    {
        $pattern = '/<form(.*?)action=(["\'])(.*?)\2(.*?)>/';
        $translatedPage = '<form action="https://example.com/process">';
        $type = 'form';

        // Mock Plugin ReplaceLinkService behavior
        $mockReplaceLinkService = $this->createMock(Plugin::getInstance()->getReplaceLinkService()::class);
        $mockReplaceLinkService->expects($this->once())
                               ->method('replaceForm')
                               ->willReturn('<form action="https://translated.com/process">');

        Plugin::getInstance()->method('getReplaceLinkService')->willReturn($mockReplaceLinkService);

        $result = $this->service->modifyLink($pattern, $translatedPage, $type);

        $this->assertSame('<form action="https://translated.com/process">', $result);
    }
}
