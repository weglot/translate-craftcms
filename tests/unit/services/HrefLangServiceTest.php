<?php

declare(strict_types=1);

namespace weglot\craftweglot\tests\unit\services;

use craft\base\Component;
use PHPUnit\Framework\TestCase;
use weglot\craftweglot\Plugin;
use weglot\craftweglot\services\HrefLangService;
use weglot\craftweglot\services\LanguageService;
use weglot\craftweglot\services\RequestUrlService;
use weglot\craftweglot\services\SlugService;
use Weglot\Vendor\Weglot\Client\Api\LanguageEntry;
use Weglot\Vendor\Weglot\Util\Regex;
use Weglot\Vendor\Weglot\Util\Regex\RegexEnum;
use Weglot\Vendor\Weglot\Util\Url;

final class HrefLangServiceTest extends TestCase
{
    private const REPLACED_COMPONENTS = ['language', 'requestUrlService', 'slug'];

    private HrefLangService $service;

    /** @var array<string, Component> */
    private array $originalComponents = [];

    private string $savedApiKey = '';

    protected function setUp(): void
    {
        parent::setUp();
        foreach (self::REPLACED_COMPONENTS as $id) {
            $component = Plugin::getInstance()->get($id);
            if ($component instanceof Component) {
                $this->originalComponents[$id] = $component;
            }
        }
        $this->savedApiKey = Plugin::getInstance()->getTypedSettings()->apiKey;
        Plugin::getInstance()->getTypedSettings()->apiKey = 'wg_test';
        $this->injectSlugStub([]);

        // Stub LanguageService: return 'en' as original without API calls.
        $langStub = new class extends LanguageService {
            public function getOriginalLanguage(): LanguageEntry
            {
                return new LanguageEntry('en', 'en', 'English', 'English', false);
            }
        };
        Plugin::getInstance()->set('language', $langStub);

        $this->service = new HrefLangService();
    }

    protected function tearDown(): void
    {
        // Guarded: setUp() may have thrown before every component was captured.
        foreach ($this->originalComponents as $id => $component) {
            Plugin::getInstance()->set($id, $component);
        }
        Plugin::getInstance()->getTypedSettings()->apiKey = $this->savedApiKey;
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Build a RequestUrlService stub with full control over every value read
     * by generateHrefLangTags():
     *   - isEligibleUrl() → $eligible (non-empty = eligible)
     *   - getCurrentLanguage() → $currentLang
     *   - getWeglotUrl()->getAllUrls() → $allUrls.
     *
     * @param array<int, array{language: LanguageEntry, url: string, excluded: bool, exclusion_behavior: string, language_button_displayed: bool}> $allUrls
     */
    private function injectRequestUrlStub(
        bool $eligible,
        ?LanguageEntry $currentLang,
        array $allUrls = [],
    ): void {
        // Anonymous subclass of Url that bypasses constructor and returns
        // controlled data from getAllUrls() — the only Url method called in
        // generateHrefLangTags().
        $originalLang = new LanguageEntry('en', 'en', 'English', 'English', false);

        $urlStub = new class($allUrls, $originalLang) extends Url {
            /**
             * @param array<int, array{language: LanguageEntry, url: string, excluded: bool, exclusion_behavior: string, language_button_displayed: bool}> $mockedUrls
             */
            public function __construct(private readonly array $mockedUrls, LanguageEntry $originalLang)
            {
                // Provide minimal valid args so parent initialises its properties
                // without touching any live service (no API key, no excluded URLs).
                parent::__construct('https://example.com', $originalLang, [], '', [], []);
            }

            /** @return array<int, array{language: LanguageEntry, url: string, excluded: bool, exclusion_behavior: string, language_button_displayed: bool}> */
            public function getAllUrls(): array
            {
                return $this->mockedUrls;
            }
        };

        $stub = new class($eligible, $currentLang, $urlStub) extends RequestUrlService {
            public function __construct(
                private readonly bool $isEligible,
                private readonly ?LanguageEntry $currentLanguage,
                private readonly Url $urlObject,
            ) {
                parent::__construct();
            }

            public function getFullUrl(bool $useForwardedHost = false): string
            {
                return 'https://example.com/page';
            }

            public function isEligibleUrl(string $url): array
            {
                return $this->isEligible ? ['fr'] : [];
            }

            public function getCurrentLanguage(): ?LanguageEntry
            {
                return $this->currentLanguage;
            }

            public function getWeglotUrl(): Url
            {
                return $this->urlObject;
            }

            public function getSourceUrl(): string
            {
                return 'https://example.com/page';
            }

            public function createUrlObject(string $url): Url
            {
                return $this->urlObject;
            }
        };

        Plugin::getInstance()->set('requestUrlService', $stub);
    }

    /**
     * Real Url objects on $requestUrl: original 'en', destinations as given.
     *
     * @param list<LanguageEntry>                  $destinations
     * @param array<string, array<string, string>> $customUrls   internal code => [translated => original]
     * @param array<int, mixed>                    $excludeUrls
     */
    private function injectRealRequestUrl(string $requestUrl, array $destinations, array $customUrls = [], array $excludeUrls = []): void
    {
        $en = $this->langEntry('en', 'en');
        Plugin::getInstance()->set('language', new class($en, $destinations) extends LanguageService {
            /** @param list<LanguageEntry> $destinations */
            public function __construct(private readonly LanguageEntry $original, private readonly array $destinations)
            {
                parent::__construct();
            }

            public function getOriginalLanguage(): LanguageEntry
            {
                return $this->original;
            }

            /** @return list<LanguageEntry> */
            public function getDestinationLanguages(): array
            {
                return $this->destinations;
            }
        });

        $factory = static fn (string $url): Url => new Url($url, $en, $destinations, '', $excludeUrls, $customUrls);
        Plugin::getInstance()->set('requestUrlService', new class($factory($requestUrl), $factory) extends RequestUrlService {
            /** @param \Closure(string): Url $factory */
            public function __construct(private readonly Url $url, private readonly \Closure $factory)
            {
                parent::__construct();
            }

            public function getWeglotUrl(): Url
            {
                return $this->url;
            }

            public function createUrlObject(string $url): Url
            {
                return ($this->factory)($url);
            }

            public function getFullUrl(bool $useForwardedHost = false): string
            {
                return $this->url->getUrl();
            }
        });
    }

    /**
     * @param array<string, array<string, string>> $forward language code => [source slug => translated slug]
     */
    private function injectSlugStub(array $forward): void
    {
        Plugin::getInstance()->set('slug', new class($forward) extends SlugService {
            /** @param array<string, array<string, string>> $forward */
            public function __construct(private readonly array $forward)
            {
                parent::__construct();
            }

            public function getSlugMapsFromCacheWithApiKey(string $apiKey, array $destinationLanguages): array
            {
                $maps = [];
                foreach ($this->forward as $code => $map) {
                    $maps[$code] = ['forward' => $map, 'reverse' => array_flip($map)];
                }

                return $maps;
            }
        });
    }

    private function langEntry(string $internal, string $external): LanguageEntry
    {
        return new LanguageEntry($internal, $external, $internal, $internal, false);
    }

    /**
     * A complete Url::getAllUrls() row. The last two keys are never read by
     * HrefLangService, but the stub has to honour the parent's return shape.
     *
     * @return array{language: LanguageEntry, url: string, excluded: bool, exclusion_behavior: string, language_button_displayed: bool}
     */
    private function urlEntry(string $url, LanguageEntry $language, bool $excluded): array
    {
        return [
            'language' => $language,
            'url' => $url,
            'excluded' => $excluded,
            'exclusion_behavior' => '',
            'language_button_displayed' => true,
        ];
    }

    // -------------------------------------------------------------------------
    // generateHrefLangTags
    // -------------------------------------------------------------------------

    public function testGenerateReturnsNewlineOnlyWhenUrlIsNotEligible(): void
    {
        $this->injectRequestUrlStub(eligible: false, currentLang: null);

        self::assertSame("\n", $this->service->generateHrefLangTags());
    }

    public function testGenerateReturnsNewlineOnlyWhenAllUrlsIsEmpty(): void
    {
        $this->injectRequestUrlStub(eligible: true, currentLang: null, allUrls: []);

        self::assertSame("\n", $this->service->generateHrefLangTags());
    }

    public function testGenerateSkipsExcludedUrls(): void
    {
        $this->injectRequestUrlStub(eligible: true, currentLang: null, allUrls: [
            $this->urlEntry('https://example.com/en', $this->langEntry('en', 'en'), false),
            $this->urlEntry('https://example.com/fr', $this->langEntry('fr', 'fr'), true),
        ]);

        $output = $this->service->generateHrefLangTags();

        self::assertStringContainsString('href="https://example.com/en"', $output);
        self::assertStringNotContainsString('href="https://example.com/fr"', $output);
    }

    public function testGenerateProducesOneLinkTagPerNonExcludedLanguage(): void
    {
        $this->injectRequestUrlStub(eligible: true, currentLang: null, allUrls: [
            $this->urlEntry('https://example.com/en', $this->langEntry('en', 'en'), false),
            $this->urlEntry('https://example.com/fr', $this->langEntry('fr', 'fr'), false),
            $this->urlEntry('https://example.com/de', $this->langEntry('de', 'de'), false),
        ]);

        $output = $this->service->generateHrefLangTags();

        self::assertSame(3, substr_count($output, '<link rel="alternate"'));
    }

    public function testGenerateUsesExternalCodeAsHreflangAttribute(): void
    {
        // Internal code is 'zh', external (BCP-47) code is 'zh-TW' — the tag
        // must expose the external code, not the internal one.
        $this->injectRequestUrlStub(eligible: true, currentLang: null, allUrls: [
            $this->urlEntry('https://example.com/zh-tw', $this->langEntry('zh', 'zh-TW'), false),
        ]);

        $output = $this->service->generateHrefLangTags();

        self::assertStringContainsString('hreflang="zh-TW"', $output);
        self::assertStringNotContainsString('hreflang="zh"', $output);
    }

    public function testGenerateEscapesSpecialCharactersInHref(): void
    {
        $this->injectRequestUrlStub(eligible: true, currentLang: null, allUrls: [
            $this->urlEntry('https://example.com/page?a=1&b=2', $this->langEntry('fr', 'fr'), false),
        ]);

        $output = $this->service->generateHrefLangTags();

        // Query strings are stripped before output (explode on '?')
        self::assertStringContainsString('href="https://example.com/page"', $output);
        self::assertStringNotContainsString('?a=1', $output);
    }

    public function testGenerateLinkTagHasCorrectStructure(): void
    {
        $this->injectRequestUrlStub(eligible: true, currentLang: null, allUrls: [
            $this->urlEntry('https://example.com/fr', $this->langEntry('fr', 'fr'), false),
        ]);

        $output = $this->service->generateHrefLangTags();

        self::assertStringContainsString('<link rel="alternate" href="https://example.com/fr" hreflang="fr"/>', $output);
    }

    // -------------------------------------------------------------------------
    // injectHrefLangTags
    // -------------------------------------------------------------------------

    public function testInjectDoesNothingWhenApiKeyIsEmpty(): void
    {
        // Ensure API key is empty (default settings).
        $settings = Plugin::getInstance()->getTypedSettings();
        $settings->apiKey = '';

        // injectHrefLangTags must return without calling generateHrefLangTags —
        // we verify indirectly by ensuring no exception is thrown and the method
        // returns void cleanly even when requestUrlService is not stubbed.
        $this->expectNotToPerformAssertions();
        $this->service->injectHrefLangTags();
    }

    public function testTranslatedPageAnnouncesEachLanguageWithItsOwnSlug(): void
    {
        $this->injectSlugStub([
            'fr' => ['guestbook' => 'livre-d-or'],
            'oc' => ['guestbook' => 'libre-d-ostes'],
            'it' => ['guestbook' => 'libro-degli-ospiti'],
        ]);
        $this->injectRealRequestUrl('https://example.com/it/libro-degli-ospiti/', [
            $this->langEntry('fr', 'fr'), $this->langEntry('oc', 'oc'), $this->langEntry('it', 'it'),
        ]);

        $output = $this->service->generateHrefLangTags();

        self::assertStringContainsString('href="https://example.com/guestbook/" hreflang="en"', $output);
        self::assertStringContainsString('href="https://example.com/fr/livre-d-or/" hreflang="fr"', $output);
        self::assertStringContainsString('href="https://example.com/oc/libre-d-ostes/" hreflang="oc"', $output);
        self::assertStringContainsString('href="https://example.com/it/libro-degli-ospiti/" hreflang="it"', $output);
    }

    public function testNestedSlugsAreTranslatedSegmentBySegment(): void
    {
        $this->injectSlugStub([
            'fr' => ['blog' => 'blogue', 'demo-article' => 'article-demo'],
            'it' => ['blog' => 'blog-it', 'demo-article' => 'articolo-demo'],
        ]);
        $this->injectRealRequestUrl('https://example.com/it/blog-it/articolo-demo', [
            $this->langEntry('fr', 'fr'), $this->langEntry('it', 'it'),
        ]);

        $output = $this->service->generateHrefLangTags();

        self::assertStringContainsString('href="https://example.com/blog/demo-article" hreflang="en"', $output);
        self::assertStringContainsString('href="https://example.com/fr/blogue/article-demo" hreflang="fr"', $output);
    }

    public function testLanguageCodeWithCapitalsGetsItsTranslatedSlug(): void
    {
        $this->injectSlugStub(['pt-br' => ['guestbook' => 'livro-de-visitas']]);
        $this->injectRealRequestUrl('https://example.com/guestbook', [$this->langEntry('pt-br', 'pt-BR')]);

        $output = $this->service->generateHrefLangTags();

        self::assertStringContainsString('href="https://example.com/pt-BR/livro-de-visitas" hreflang="pt-BR"', $output);
    }

    public function testCustomUrlsAreKept(): void
    {
        $this->injectRealRequestUrl(
            'https://example.com/guestbook',
            [$this->langEntry('fr', 'fr')],
            ['fr' => ['livre-perso' => 'guestbook']],
        );

        $output = $this->service->generateHrefLangTags();

        self::assertStringContainsString('href="https://example.com/fr/livre-perso" hreflang="fr"', $output);
    }

    public function testGenerateEscapesQuotesAndAngleBracketsInHref(): void
    {
        $this->injectRequestUrlStub(eligible: true, currentLang: null, allUrls: [
            $this->urlEntry('https://example.com/a"b<c', $this->langEntry('en', 'en'), false),
        ]);

        $output = $this->service->generateHrefLangTags();

        self::assertStringContainsString('href="https://example.com/a&quot;b&lt;c"', $output);
    }

    public function testExclusionFlagsComeFromTheRequestedUrl(): void
    {
        $fr = $this->langEntry('fr', 'fr');
        $it = $this->langEntry('it', 'it');
        $this->injectSlugStub([
            'fr' => ['guestbook' => 'livre-d-or'],
            'it' => ['guestbook' => 'libro-degli-ospiti'],
        ]);
        // Matches the requested (translated) path only, never the source one.
        $this->injectRealRequestUrl(
            'https://example.com/it/libro-degli-ospiti/',
            [$fr, $it],
            [],
            [[new Regex(RegexEnum::START_WITH, '/libro-degli-ospiti'), [$fr]]],
        );

        $output = $this->service->generateHrefLangTags();

        self::assertStringNotContainsString('hreflang="fr"', $output);
        self::assertStringContainsString('href="https://example.com/it/libro-degli-ospiti/" hreflang="it"', $output);
    }
}
