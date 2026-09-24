<?php

declare(strict_types=1);

namespace weglot\craftweglot\tests\unit\services;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use weglot\craftweglot\services\DomCheckersService;
use weglot\craftweglot\services\LanguageService;
use weglot\craftweglot\services\OptionService;
use weglot\craftweglot\services\ParserService;
use weglot\craftweglot\services\RegexCheckersService;
use weglot\craftweglot\services\ReplaceUrlService;
use weglot\craftweglot\services\RequestUrlService;
use weglot\craftweglot\services\TranslateService;
use Weglot\Vendor\Weglot\Client\Api\Exception\ApiError;
use Weglot\Vendor\Weglot\Client\Api\LanguageEntry;
use Weglot\Vendor\Weglot\Parser\TranslatingParser;
use Weglot\Vendor\Weglot\Util\Url;

final class TranslateServiceTest extends TestCase
{
    // Shared LanguageEntry instances — must be the same objects used both in the
    // Url stub's allUrls array AND in the service stubs, because
    // Url::getForLanguage() uses strict === comparison on the language field.
    private LanguageEntry $en;
    private LanguageEntry $fr;

    protected function setUp(): void
    {
        parent::setUp();
        $this->en = new LanguageEntry('en', 'en', 'English', 'English', false);
        $this->fr = new LanguageEntry('fr', 'fr', 'French', 'Français', false);

        // reverseTranslateSearchQuery() reads the request's absolute URL, and Yii
        // cannot resolve a URI under the test bootstrap. Seed one so this class does
        // not depend on an earlier test having populated it.
        //
        // Not restored afterwards: the request is a component of the single shared
        // Craft app, and it caches the URL on first resolution anyway, so there is no
        // "unresolved" value to put back. Any class needing a URL must seed its own.
        \Craft::$app->getRequest()->setUrl('/');
    }

    // -------------------------------------------------------------------------
    // Stub factories
    // -------------------------------------------------------------------------

    /**
     * Url subclass whose getAllUrls() returns controlled entries for $this->en
     * and $this->fr so that getForLanguage() resolves correctly via === check.
     */
    private function makeUrlStub(): Url
    {
        $en = $this->en;
        $fr = $this->fr;

        return new class([$en, $fr]) extends Url {
            /** @var array<int, array{url: string, language: LanguageEntry, excluded: bool, exclusion_behavior: string, language_button_displayed: bool}> */
            private readonly array $mockedUrls;

            /** @param LanguageEntry[] $langs */
            public function __construct(array $langs)
            {
                $this->mockedUrls = [
                    ['url' => 'https://example.com/', 'language' => $langs[0], 'excluded' => false, 'exclusion_behavior' => '', 'language_button_displayed' => true],
                    ['url' => 'https://example.com/fr/', 'language' => $langs[1], 'excluded' => false, 'exclusion_behavior' => '', 'language_button_displayed' => true],
                ];
                parent::__construct('https://example.com', $langs[0], [], '', [], []);
            }

            /** @return array<int, array{url: string, language: LanguageEntry, excluded: bool, exclusion_behavior: string, language_button_displayed: bool}> */
            public function getAllUrls(): array
            {
                return $this->mockedUrls;
            }
        };
    }

    private function makeLangStub(LanguageEntry $original): LanguageService
    {
        return new class($original) extends LanguageService {
            public function __construct(private readonly LanguageEntry $lang)
            {
                parent::__construct();
            }

            public function getOriginalLanguage(): LanguageEntry
            {
                return $this->lang;
            }
        };
    }

    private function makeRequestUrlStub(LanguageEntry $current, Url $url): RequestUrlService
    {
        return new class($current, $url) extends RequestUrlService {
            public function __construct(
                private readonly LanguageEntry $currentLang,
                private readonly Url $urlStub,
            ) {
                parent::__construct();
            }

            public function getCurrentLanguage(): LanguageEntry
            {
                return $this->currentLang;
            }

            public function getWeglotUrl(): Url
            {
                return $this->urlStub;
            }
        };
    }

    /**
     * ParserService stub whose parser either acts as identity, throws ApiError,
     * or throws a generic exception.
     */
    private function makeParserStub(
        bool $throwsApiError = false,
        bool $throwsException = false,
    ): ParserService {
        /** @var TranslatingParser&MockObject $parserMock */
        $parserMock = $this->getMockBuilder(TranslatingParser::class)
            ->disableOriginalConstructor()
            ->getMock();

        if ($throwsApiError) {
            $parserMock->method('translate')->willThrowException(new ApiError('API failure'));
        } elseif ($throwsException) {
            $parserMock->method('translate')->willThrowException(new \RuntimeException('generic failure'));
        } else {
            // Identity: return source content unchanged (no real API call needed).
            $parserMock->method('translate')->willReturnArgument(0);
        }

        $mock = $parserMock;

        return new class($mock) extends ParserService {
            public function __construct(private readonly TranslatingParser $mockParser)
            {
                // ParserService constructor requires these three; they have no
                // constructor injection of their own so new() is safe here.
                parent::__construct(new OptionService(), new DomCheckersService(), new RegexCheckersService());
            }

            public function getParser(): TranslatingParser
            {
                return $this->mockParser;
            }
        };
    }

    /** OptionService stub: no API calls; an AI disclaimer only when a selector is given. */
    private function makeOptionStub(?string $aiDisclaimerSelector = null): OptionService
    {
        return new class($aiDisclaimerSelector) extends OptionService {
            public function __construct(private readonly ?string $aiDisclaimerSelector)
            {
                parent::__construct();
            }

            /** @return array<string, string>|null */
            public function getOption(string $key): ?array
            {
                if ('custom_settings' === $key && null !== $this->aiDisclaimerSelector) {
                    return ['ai_disclaimer_selector' => $this->aiDisclaimerSelector];
                }

                return null;
            }
        };
    }

    /**
     * Build a fully-wired TranslateService for testing.
     *
     * @param ReplaceUrlService|null $replaceUrlStub pass a custom spy to inspect calls
     */
    private function makeService(
        LanguageEntry $original,
        LanguageEntry $current,
        bool $parserThrowsApiError = false,
        bool $parserThrowsException = false,
        ?ReplaceUrlService $replaceUrlStub = null,
        ?string $aiDisclaimerSelector = null,
    ): TranslateService {
        return new TranslateService(
            languageService: $this->makeLangStub($original),
            requestUrlService: $this->makeRequestUrlStub($current, $this->makeUrlStub()),
            parserService: $this->makeParserStub($parserThrowsApiError, $parserThrowsException),
            replaceUrlService: $replaceUrlStub ?? new ReplaceUrlService(),
            optionService: $this->makeOptionStub($aiDisclaimerSelector),
        );
    }

    // -------------------------------------------------------------------------
    // processResponse — early returns
    // -------------------------------------------------------------------------

    public function testProcessResponseReturnsEmptyStringAsIs(): void
    {
        self::assertSame('', $this->makeService($this->en, $this->en)->processResponse(''));
    }

    public function testProcessResponseReturnsZeroStringAsIs(): void
    {
        self::assertSame('0', $this->makeService($this->en, $this->en)->processResponse('0'));
    }

    // -------------------------------------------------------------------------
    // processResponse — same language (no translation)
    // -------------------------------------------------------------------------

    public function testProcessResponseReturnsJsonUnchangedWhenCurrentEqualsOriginal(): void
    {
        $json = '{"title":"Hello"}';
        self::assertSame($json, $this->makeService($this->en, $this->en)->processResponse($json));
    }

    public function testProcessResponseReturnsXmlUnchangedWhenCurrentEqualsOriginal(): void
    {
        $xml = '<?xml version="1.0"?><root><item>Hello</item></root>';
        self::assertSame($xml, $this->makeService($this->en, $this->en)->processResponse($xml));
    }

    public function testProcessResponsePassesHtmlThroughRenderDomOnSameLanguage(): void
    {
        // weglotRenderDom adds translate="no" — verifies the HTML path is taken.
        $html = '<html><head></head><body>Hello</body></html>';
        $output = $this->makeService($this->en, $this->en)->processResponse($html);
        self::assertStringContainsString('translate="no"', $output);
    }

    // -------------------------------------------------------------------------
    // processResponse — error handling
    // -------------------------------------------------------------------------

    public function testProcessResponseAppendsApiErrorCommentForHtml(): void
    {
        $html = '<html><body>Hello</body></html>';
        $output = $this->makeService($this->en, $this->fr, parserThrowsApiError: true)
            ->processResponse($html);
        self::assertStringContainsString('<!--Weglot error API :', $output);
    }

    public function testProcessResponseAppendsGenericErrorCommentForHtml(): void
    {
        $html = '<html><body>Hello</body></html>';
        $output = $this->makeService($this->en, $this->fr, parserThrowsException: true)
            ->processResponse($html);
        self::assertStringContainsString('<!--Weglot error :', $output);
    }

    public function testProcessResponseDoesNotAppendErrorCommentForJson(): void
    {
        // JSON errors are silently swallowed; the original JSON is returned as-is.
        $json = '{"title":"Hello"}';
        $output = $this->makeService($this->en, $this->fr, parserThrowsException: true)
            ->processResponse($json);
        self::assertStringNotContainsString('<!--', $output);
    }

    // -------------------------------------------------------------------------
    // weglotRenderDom — translate="no" injection
    // -------------------------------------------------------------------------

    public function testWeglotRenderDomAddsTranslateNoToHtmlTag(): void
    {
        $output = $this->makeService($this->en, $this->en)
            ->weglotRenderDom('<html><body></body></html>');
        self::assertStringContainsString('<html translate="no">', $output);
    }

    public function testWeglotRenderDomDoesNotDuplicateTranslateNoWhenAlreadyPresent(): void
    {
        $output = $this->makeService($this->en, $this->en)
            ->weglotRenderDom('<html translate="no"><body></body></html>');
        self::assertSame(1, substr_count($output, 'translate="no"'));
    }

    // -------------------------------------------------------------------------
    // weglotRenderDom — canonical tag
    // -------------------------------------------------------------------------

    public function testWeglotRenderDomStripsExistingCanonicalTag(): void
    {
        $html = '<html><head><link rel="canonical" href="https://old.example.com/page"/></head></html>';
        $output = $this->makeService($this->en, $this->en)->weglotRenderDom($html);
        self::assertStringNotContainsString('https://old.example.com/page', $output);
    }

    public function testWeglotRenderDomInjectsCanonicalBeforeHeadClose(): void
    {
        $html = '<html><head></head><body></body></html>';
        $output = $this->makeService($this->en, $this->en)->weglotRenderDom($html);
        self::assertMatchesRegularExpression('/<link rel="canonical"[^>]+>\s*<\/head>/i', $output);
    }

    public function testWeglotRenderDomPrependsCanonicalWhenNoHeadCloseTag(): void
    {
        // When </head> is absent, the canonical is prepended to the whole output.
        $html = '<html><body>No head close tag</body></html>';
        $output = $this->makeService($this->en, $this->en)->weglotRenderDom($html);
        self::assertStringStartsWith('<link rel="canonical"', trim($output));
    }

    // -------------------------------------------------------------------------
    // weglotRenderDom — URL replacement
    // -------------------------------------------------------------------------

    public function testWeglotRenderDomCallsReplaceLinkInDomWhenOnTranslatedPage(): void
    {
        $spy = new class extends ReplaceUrlService {
            public bool $wasCalled = false;

            public function replaceLinkInDom(string $dom): string
            {
                $this->wasCalled = true;

                return $dom;
            }
        };

        $this->makeService($this->en, $this->fr, replaceUrlStub: $spy)
            ->weglotRenderDom('<html><body>Hello</body></html>');

        self::assertTrue($spy->wasCalled);
    }

    public function testWeglotRenderDomDoesNotCallReplaceLinkInDomOnOriginalPage(): void
    {
        $spy = new class extends ReplaceUrlService {
            public bool $wasCalled = false;

            public function replaceLinkInDom(string $dom): string
            {
                $this->wasCalled = true;

                return $dom;
            }
        };

        $this->makeService($this->en, $this->en, replaceUrlStub: $spy)
            ->weglotRenderDom('<html><body>Hello</body></html>');

        self::assertFalse($spy->wasCalled);
    }

    // -------------------------------------------------------------------------
    // processResponse — AI disclaimer
    // -------------------------------------------------------------------------

    private const AI_DISCLAIMER = 'Translated content on this website may be generated using artificial intelligence.';

    private const DISCLAIMER_PAGE = '<html><head><script>if (a<b) { x="</div>"; }</script></head><body>'
        .'<main><p class="footer">Main &copy; &nbsp;é</p></main>'
        .'<footer id="footer" class="footer" data-ai="1"><p>First</p></footer>'
        .'<footer class="footer"><p>Second</p></footer><img id="logo" src="/logo.png"></body></html>';

    private function translateWithDisclaimer(string $html, ?string $selector): string
    {
        $identity = new class extends ReplaceUrlService {
            public function replaceLinkInDom(string $dom): string
            {
                return $dom;
            }
        };

        return $this->makeService($this->en, $this->fr, replaceUrlStub: $identity, aiDisclaimerSelector: $selector)
            ->processResponse($html);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function matchingDisclaimerSelectorProvider(): array
    {
        return [
            'id' => ['#footer', 'First'],
            'class' => ['.footer', 'Main'],
            'tag' => ['footer', 'First'],
            'attribute' => ['[data-ai="1"]', 'First'],
            'descendant' => ['body footer', 'First'],
        ];
    }

    #[DataProvider('matchingDisclaimerSelectorProvider')]
    public function testDisclaimerIsAppendedOnceToTheFirstMatch(string $selector, string $firstMatchText): void
    {
        $output = $this->translateWithDisclaimer(self::DISCLAIMER_PAGE, $selector);

        self::assertSame(1, substr_count($output, self::AI_DISCLAIMER));
        self::assertMatchesRegularExpression('/'.preg_quote($firstMatchText, '/').'[^<]*(<\/p>)?\s*'.preg_quote(self::AI_DISCLAIMER, '/').'/', $output);
    }

    public function testDisclaimerInjectionKeepsInlineScriptsAndEntitiesIntact(): void
    {
        $output = $this->translateWithDisclaimer(self::DISCLAIMER_PAGE, '#footer');

        self::assertStringContainsString('<script>if (a<b) { x="</div>"; }</script>', $output);
        self::assertStringContainsString('Main &copy; &nbsp;é', $output);
    }

    public function testDisclaimerInjectionKeepsAScriptInsideTheTargetIntact(): void
    {
        $page = '<html><body><footer id="footer"><script>var x = "</div>";</script><p>First</p></footer></body></html>';

        $output = $this->translateWithDisclaimer($page, '#footer');

        self::assertStringContainsString('<script>var x = "</div>";</script><p>First</p> '.self::AI_DISCLAIMER, $output);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function unusableDisclaimerSelectorProvider(): array
    {
        return [
            'no match' => ['#missing'],
            'quote' => ["#a'b"],
            'garbage' => ['>>>'],
            'combinator only' => ['>'],
            'comma' => [','],
            'void element' => ['#logo'],
            'script element' => ['head script'],
        ];
    }

    #[DataProvider('unusableDisclaimerSelectorProvider')]
    public function testUnusableDisclaimerSelectorLeavesThePageAsWithoutDisclaimer(string $selector): void
    {
        self::assertSame(
            $this->translateWithDisclaimer(self::DISCLAIMER_PAGE, null),
            $this->translateWithDisclaimer(self::DISCLAIMER_PAGE, $selector),
        );
    }

    // -------------------------------------------------------------------------
    // reverseTranslateSearchQuery
    // -------------------------------------------------------------------------

    public function testReverseTranslateReturnsEmptyStringForBlankQuery(): void
    {
        $svc = $this->makeService($this->en, $this->fr);
        self::assertSame('', $svc->reverseTranslateSearchQuery('   '));
    }

    public function testReverseTranslateTruncatesQueryLongerThan200Chars(): void
    {
        // API key is empty in the test bootstrap → returns the (truncated) query unchanged.
        $svc = $this->makeService($this->en, $this->fr);
        $result = $svc->reverseTranslateSearchQuery(str_repeat('a', 250), $this->fr, $this->en);
        self::assertSame(200, mb_strlen($result));
    }

    public function testReverseTranslateReturnsQueryUnchangedWhenCurrentEqualsOriginal(): void
    {
        $svc = $this->makeService($this->en, $this->en);
        self::assertSame('search term', $svc->reverseTranslateSearchQuery('search term', $this->en, $this->en));
    }

    public function testReverseTranslateReturnsQueryUnchangedWhenApiKeyIsEmpty(): void
    {
        // The test bootstrap leaves apiKey empty → method returns early before any HTTP call.
        $svc = $this->makeService($this->en, $this->fr);
        self::assertSame('search term', $svc->reverseTranslateSearchQuery('search term', $this->fr, $this->en));
    }
}
