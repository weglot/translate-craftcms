<?php

declare(strict_types=1);

namespace weglot\craftweglot\tests\services;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use weglot\craftweglot\helpers\HelperReplaceUrl;
use weglot\craftweglot\Plugin;
use weglot\craftweglot\services\ReplaceLinkService;
use weglot\craftweglot\services\ReplaceUrlService;
use weglot\craftweglot\services\RequestUrlService;
use Weglot\Vendor\Weglot\Client\Api\LanguageEntry;

class ReplaceUrlServiceTest extends TestCase
{
    private ReplaceUrlService $service;

    private ?ReplaceLinkService $originalReplaceLinkService = null;

    private ?RequestUrlService $originalRequestUrlService = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalReplaceLinkService = Plugin::getInstance()->getReplaceLinkService();
        $this->originalRequestUrlService = Plugin::getInstance()->getRequestUrlService();
        $this->service = new ReplaceUrlService();
    }

    protected function tearDown(): void
    {
        // Restore the real component so a mock set in one test does not leak.
        // Guarded: setUp() may have thrown before the property was assigned,
        // and PHPUnit still calls tearDown() in that case.
        if ($this->originalReplaceLinkService instanceof ReplaceLinkService) {
            Plugin::getInstance()->set('replaceLinkService', $this->originalReplaceLinkService);
        }
        if ($this->originalRequestUrlService instanceof RequestUrlService) {
            Plugin::getInstance()->set('requestUrlService', $this->originalRequestUrlService);
        }
        parent::tearDown();
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

        self::assertSame($translatedPage, $result);
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
        $mockReplaceLinkService = $this->createMock(ReplaceLinkService::class);
        $mockReplaceLinkService->expects($this->once())
                               ->method('replaceA')
                               ->willReturn('<a href="https://example-translated.com">Example</a>');

        Plugin::getInstance()->set('replaceLinkService', $mockReplaceLinkService);

        $result = $this->service->modifyLink($pattern, $translatedPage, $type);

        self::assertSame('<a href="https://example-translated.com">Example</a>', $result);
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

        self::assertSame($translatedPage, $result);
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

        self::assertSame($translatedPage, $result);
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
        $mockReplaceLinkService = $this->createMock(ReplaceLinkService::class);
        $mockReplaceLinkService->expects($this->once())
                               ->method('replaceForm')
                               ->willReturn('<form action="https://translated.com/process">');

        Plugin::getInstance()->set('replaceLinkService', $mockReplaceLinkService);

        $result = $this->service->modifyLink($pattern, $translatedPage, $type);

        self::assertSame('<form action="https://translated.com/process">', $result);
    }

    /**
     * Patterns rewritten through a page-wide preg_replace() on the tag start and URL, where an
     * excluded tag sharing both with a rewritten one could be rewritten too.
     *
     * @return array<string, array{string, string, string, string}>
     */
    public static function pageWideReplaceProvider(): array
    {
        $div = ['<div ', '<div class="wg-excluded-link" '];

        return [
            'data-link' => ['datalink', ...$div, 'data-link'],
            'data-url' => ['dataurl', ...$div, 'data-url'],
            'data-cart-url' => ['datacart', ...$div, 'data-cart-url'],
            'hx-get' => ['hxget', ...$div, 'hx-get'],
            'hx-post' => ['hxpost', ...$div, 'hx-post'],
            'hx-put' => ['hxput', ...$div, 'hx-put'],
            'hx-patch' => ['hxpatch', ...$div, 'hx-patch'],
            'hx-delete' => ['hxdelete', ...$div, 'hx-delete'],
            'form' => ['form', '<form method="post" ', '<form class="wg-excluded-link" method="post" ', 'action'],
            'canonical' => ['canonical', '<link rel="canonical" ', '<link rel="canonical" class="wg-excluded-link" ', 'href'],
        ];
    }

    #[DataProvider('pageWideReplaceProvider')]
    public function testModifyLinkLeavesExcludedTwinUntouched(string $key, string $tagStart, string $excludedTagStart, string $attribute): void
    {
        $this->useReplaceLinkServiceTranslatingTo('/fr/about');

        $plain = $tagStart.$attribute.'="/about">';
        $excluded = $tagStart.$attribute.'="/about" class="wg-excluded-link">';

        foreach ([$plain.$excluded, $excluded.$plain] as $page) {
            $result = $this->service->modifyLink(HelperReplaceUrl::getReplaceModifyLink()[$key], $page, $key);

            self::assertStringContainsString($attribute.'="/fr/about"', $result);
            self::assertStringContainsString($attribute.'="/about" class="wg-excluded-link"', $result);
        }
    }

    #[DataProvider('pageWideReplaceProvider')]
    public function testModifyLinkLeavesElementExcludedBeforeTheAttributeUntouched(string $key, string $tagStart, string $excludedTagStart, string $attribute): void
    {
        $this->useReplaceLinkServiceTranslatingTo('/fr/about');

        $page = $excludedTagStart.$attribute.'="/about">';

        self::assertSame($page, $this->service->modifyLink(HelperReplaceUrl::getReplaceModifyLink()[$key], $page, $key));
    }

    private function useReplaceLinkServiceTranslatingTo(string $translatedUrl): void
    {
        $fr = new LanguageEntry('fr', 'fr', 'French', 'Français', false);
        $requestUrlService = new class($fr) extends RequestUrlService {
            public function __construct(private readonly LanguageEntry $language)
            {
                parent::__construct();
            }

            public function getCurrentLanguage(): LanguageEntry
            {
                return $this->language;
            }
        };
        Plugin::getInstance()->set('requestUrlService', $requestUrlService);

        Plugin::getInstance()->set('replaceLinkService', new class($requestUrlService, $translatedUrl) extends ReplaceLinkService {
            public function __construct(RequestUrlService $requestUrlService, private readonly string $translatedUrl)
            {
                parent::__construct($requestUrlService);
            }

            public function replaceUrl(string $url, LanguageEntry $language, bool $evenExcluded = true): string
            {
                return $this->translatedUrl;
            }
        });
    }
}
