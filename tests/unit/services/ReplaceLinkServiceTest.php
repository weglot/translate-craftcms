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
     * Build a RequestUrlService stub whose createUrlObject() throws. The
     * same-document guard in replaceUrl() must short-circuit before any URL
     * object is built, so createUrlObject() should never be reached; if the
     * guard failed, replaceUrl() would call it and the exception would fail the
     * test.
     */
    private function makeRusRejectingUrlBuild(string $currentUrl): RequestUrlService
    {
        return new class($currentUrl) extends RequestUrlService {
            public function __construct(private readonly string $currentUrl)
            {
                parent::__construct();
            }

            public function getFullUrl(bool $useForwardedHost = false): string
            {
                return $this->currentUrl;
            }

            public function createUrlObject(string $url): Url
            {
                throw new \LogicException('createUrlObject() must not be reached for same-document references');
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

    /**
     * Non-navigational schemes carry no host, so the external-host guard cannot catch them; they
     * must still be left untouched instead of being treated as internal paths and getting a wrong
     * language prefix (e.g. mailto:a@b.com -> /fr/a@b.com/).
     *
     * @dataProvider nonNavigationalUrlProvider
     */
    public function testReplaceUrlLeavesNonNavigationalSchemesUntouched(string $url): void
    {
        $rus = $this->makeRusNoTranslation('https://example.com/page');
        $svc = new ReplaceLinkService($rus);

        self::assertSame($url, $svc->replaceUrl($url, $this->fr));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function nonNavigationalUrlProvider(): array
    {
        return [
            'mailto' => ['mailto:concierge@weglot-craft-project.ddev.site'],
            'tel' => ['tel:+33123456789'],
            'sms' => ['sms:+33123456789'],
            'javascript' => ['javascript:void(0)'],
            'data' => ['data:text/plain;base64,SGVsbG8='],
        ];
    }

    /**
     * Fragment-only and query-only hrefs are references to the current document; they must be
     * returned untouched instead of receiving a language prefix, which would rebase them onto
     * /fr/ and break in-page / SPA navigation (e.g. "#/booking/step-1?" -> "/fr/#/booking/step-1?").
     * The stub throws if replaceUrl() tries to build a URL object, so the test fails if the
     * guard does not short-circuit.
     *
     * @dataProvider sameDocumentUrlProvider
     */
    public function testReplaceUrlLeavesSameDocumentReferencesUntouched(string $url): void
    {
        $rus = $this->makeRusRejectingUrlBuild('https://example.com/event');
        $svc = new ReplaceLinkService($rus);

        self::assertSame($url, $svc->replaceUrl($url, $this->fr));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function sameDocumentUrlProvider(): array
    {
        return [
            'spa fragment route' => ['#/booking/step-1?'],
            'simple anchor' => ['#section'],
            'bare hash' => ['#'],
            'query only' => ['?foo=bar'],
            'bare question mark' => ['?'],
        ];
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

    // -------------------------------------------------------------------------
    // replaceHx* — htmx attribute substitution
    // -------------------------------------------------------------------------

    public function testReplaceHxGetReplacesUrlAndPreservesSurroundingAttributes(): void
    {
        $svc = $this->makeReplaceSvcWithTranslation($this->fr, 'https://example.com/fr/rooms/grid/111874/453607');

        $html = '<div id="htmx-rooms-453607" hx-get="https://example.com/rooms/grid/111874/453607" hx-target="#htmx-rooms-453607">';
        $out = $svc->replaceHxGet(
            $html,
            'https://example.com/rooms/grid/111874/453607',
            '"',
            '"',
            'div id="htmx-rooms-453607" ',
        );

        self::assertStringContainsString('hx-get="https://example.com/fr/rooms/grid/111874/453607"', $out);
        self::assertStringContainsString('id="htmx-rooms-453607"', $out);
        self::assertStringContainsString('hx-target="#htmx-rooms-453607"', $out);
    }

    public function testReplaceHxPostReplacesUrl(): void
    {
        $svc = $this->makeReplaceSvcWithTranslation($this->fr, 'https://example.com/fr/rooms/book/');

        $html = '<form hx-post="https://example.com/rooms/book/">';
        $out = $svc->replaceHxPost($html, 'https://example.com/rooms/book/', '"', '"', 'form ');

        self::assertStringContainsString('hx-post="https://example.com/fr/rooms/book/"', $out);
    }
}
