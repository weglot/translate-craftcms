<?php

declare(strict_types=1);

namespace weglot\craftweglot\tests\unit\services;

use PHPUnit\Framework\TestCase;
use weglot\craftweglot\Plugin;
use weglot\craftweglot\services\HrefLangService;
use weglot\craftweglot\services\LanguageService;
use weglot\craftweglot\services\RequestUrlService;
use Weglot\Vendor\Weglot\Client\Api\LanguageEntry;
use Weglot\Vendor\Weglot\Util\Url;

final class HrefLangServiceTest extends TestCase
{
    private HrefLangService $service;

    protected function setUp(): void
    {
        parent::setUp();

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
        };

        Plugin::getInstance()->set('requestUrlService', $stub);
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
}
