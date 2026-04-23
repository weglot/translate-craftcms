<?php

declare(strict_types=1);

namespace weglot\craftweglot\tests\unit\services;

use PHPUnit\Framework\TestCase;
use weglot\craftweglot\Plugin;
use weglot\craftweglot\services\ReplaceLinkService;
use weglot\craftweglot\services\RequestUrlService;
use Weglot\Vendor\Weglot\Client\Api\LanguageEntry;
use Weglot\Vendor\Weglot\Util\Url;

final class ReplaceLinkServiceTest extends TestCase
{
    private LanguageEntry $en;
    private LanguageEntry $fr;

    protected function setUp(): void
    {
        parent::setUp();
        $this->en = new LanguageEntry('en', 'en', 'English', 'English', false);
        $this->fr = new LanguageEntry('fr', 'fr', 'French', 'Français', false);
    }

    // -------------------------------------------------------------------------
    // Factories
    // -------------------------------------------------------------------------

    /**
     * Build a RequestUrlService stub whose getFullUrl() returns $currentUrl
     * and whose createUrlObject() returns a Url stub that reports no available
     * translation (getForLanguage returns false).
     */
    private function makeRusNoTranslation(string $currentUrl): RequestUrlService
    {
        $en = $this->en;

        return new class($currentUrl, $en) extends RequestUrlService {
            public function __construct(
                private readonly string $currentUrl,
                private readonly LanguageEntry $originalLang,
            ) {
                parent::__construct();
            }

            public function getFullUrl(bool $useForwardedHost = false): string
            {
                return $this->currentUrl;
            }

            public function createUrlObject(string $url): Url
            {
                $original = $this->originalLang;

                return new class($url, $original) extends Url {
                    public function __construct(string $url, LanguageEntry $original)
                    {
                        parent::__construct($url, $original, [], '', [], []);
                    }

                    public function getForLanguage($language, $evenExcluded = false): false
                    {
                        return false;
                    }
                };
            }
        };
    }

    /**
     * Build a ReplaceLinkService subclass whose replaceUrl() returns a
     * controlled translated URL, bypassing all API and service calls.
     * Also injects a matching RequestUrlService stub into the plugin so that
     * replaceA() / simpleReplace() can resolve getCurrentLanguage().
     */
    private function makeReplaceSvcWithTranslation(LanguageEntry $current, string $translatedUrl): ReplaceLinkService
    {
        // Inject RequestUrlService so that replaceA() can get getCurrentLanguage()
        $rusStub = new class($current) extends RequestUrlService {
            public function __construct(private readonly LanguageEntry $lang)
            {
                parent::__construct();
            }

            public function getCurrentLanguage(): LanguageEntry
            {
                return $this->lang;
            }
        };
        Plugin::getInstance()->set('requestUrlService', $rusStub);

        return new class($rusStub, $translatedUrl) extends ReplaceLinkService {
            public function __construct(
                RequestUrlService $rus,
                private readonly string $translated,
            ) {
                parent::__construct($rus);
            }

            public function replaceUrl(string $url, LanguageEntry $language, bool $evenExcluded = true): string
            {
                return $this->translated;
            }
        };
    }

    // -------------------------------------------------------------------------
    // replaceUrl — early returns
    // -------------------------------------------------------------------------

    public function testReplaceUrlReturnsOriginalWhenHostsDiffer(): void
    {
        // Current site is site-a.com; URL references site-b.com → no replacement.
        $rus = $this->makeRusNoTranslation('https://site-a.com/page');
        $svc = new ReplaceLinkService($rus);

        $url = 'https://site-b.com/about';
        self::assertSame($url, $svc->replaceUrl($url, $this->fr));
    }

    public function testReplaceUrlReturnsOriginalWhenNoTranslationFound(): void
    {
        // Same host, but the Url object reports no translation for 'fr'.
        $rus = $this->makeRusNoTranslation('https://example.com/page');
        $svc = new ReplaceLinkService($rus);

        $url = 'https://example.com/contact';
        self::assertSame($url, $svc->replaceUrl($url, $this->fr));
    }

    // -------------------------------------------------------------------------
    // replaceA — regex substitution
    // -------------------------------------------------------------------------

    public function testReplaceAReplacesHrefWithDoubleQuotes(): void
    {
        $svc = $this->makeReplaceSvcWithTranslation($this->fr, 'https://example.com/fr/blog/');

        $html = '<a href="https://example.com/blog/">Read</a>';
        // $sometags=' ' provides the space between <a and href in the regex
        $out = $svc->replaceA($html, 'https://example.com/blog/', '"', '"', ' ');

        self::assertStringContainsString('href="https://example.com/fr/blog/"', $out);
    }

    public function testReplaceAReplacesHrefWithSingleQuotes(): void
    {
        $svc = $this->makeReplaceSvcWithTranslation($this->fr, 'https://example.com/fr/page/');

        $html = "<a href='https://example.com/page/'>Go</a>";
        $out = $svc->replaceA($html, 'https://example.com/page/', "'", "'", ' ');

        self::assertStringContainsString("href='https://example.com/fr/page/'", $out);
    }

    public function testReplaceAPreservesAttributesAroundHref(): void
    {
        $svc = $this->makeReplaceSvcWithTranslation($this->fr, 'https://example.com/fr/blog/');

        $html = '<a class="nav" href="https://example.com/blog/" rel="nofollow">Read</a>';
        $out = $svc->replaceA($html, 'https://example.com/blog/', '"', '"', ' class="nav" ', ' rel="nofollow"');

        self::assertStringContainsString('href="https://example.com/fr/blog/"', $out);
        self::assertStringContainsString('class="nav"', $out);
        self::assertStringContainsString('rel="nofollow"', $out);
    }
}
