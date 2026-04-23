<?php

declare(strict_types=1);

namespace weglot\craftweglot\tests\unit\services;

use PHPUnit\Framework\TestCase;
use weglot\craftweglot\Plugin;
use weglot\craftweglot\services\LanguageService;
use weglot\craftweglot\services\RedirectService;
use Weglot\Vendor\Weglot\Client\Api\LanguageEntry;

final class RedirectServiceTest extends TestCase
{
    private RedirectService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Short-circuit getOriginalLanguage() to avoid API calls and return a
        // deterministic 'en' entry — getBestAvailableLanguage() appends it to
        // the available list before running its matching logic.
        $stub = new class extends LanguageService {
            public function getOriginalLanguage(): LanguageEntry
            {
                return new LanguageEntry('en', 'en', 'English', 'English', false);
            }
        };

        Plugin::getInstance()->set('language', $stub);

        $this->service = new RedirectService();
    }

    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_ACCEPT_LANGUAGE'], $_SERVER['HTTP_CF_IPCOUNTRY']);
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // getNavigatorLanguages
    // -------------------------------------------------------------------------

    public function testGetNavigatorLanguagesReturnsEmptyArrayWhenNoHeaderPresent(): void
    {
        unset($_SERVER['HTTP_ACCEPT_LANGUAGE'], $_SERVER['HTTP_CF_IPCOUNTRY']);

        self::assertSame([], $this->service->getNavigatorLanguages());
    }

    public function testGetNavigatorLanguagesParsesSingleLanguage(): void
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'fr';

        self::assertSame(['fr'], $this->service->getNavigatorLanguages());
    }

    public function testGetNavigatorLanguagesStripsQualityValues(): void
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'fr-FR,fr;q=0.9,en;q=0.8';

        self::assertSame(['fr-fr', 'fr', 'en'], $this->service->getNavigatorLanguages());
    }

    public function testGetNavigatorLanguagesReturnsLowercasedCodes(): void
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'ZH-TW,DE';

        self::assertSame(['zh-tw', 'de'], $this->service->getNavigatorLanguages());
    }

    public function testGetNavigatorLanguagesFallsBackToCloudflareCountryCode(): void
    {
        unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        $_SERVER['HTTP_CF_IPCOUNTRY'] = 'FR';

        self::assertSame(['fr'], $this->service->getNavigatorLanguages());
    }

    // -------------------------------------------------------------------------
    // getBestAvailableLanguage
    // -------------------------------------------------------------------------

    public function testGetBestAvailableLanguageReturnsNullWhenNavigatorIsEmpty(): void
    {
        self::assertNull($this->service->getBestAvailableLanguage([], ['fr', 'de']));
    }

    public function testGetBestAvailableLanguageReturnsNullWhenNoLanguageMatches(): void
    {
        // 'ja' does not match fr, de, or the appended original 'en'
        self::assertNull($this->service->getBestAvailableLanguage(['ja'], ['fr', 'de']));
    }

    public function testGetBestAvailableLanguageReturnsExactMatch(): void
    {
        self::assertSame('fr', $this->service->getBestAvailableLanguage(['fr'], ['fr', 'de']));
    }

    public function testGetBestAvailableLanguageFallsBackToPrimaryCodeFromRegionalVariant(): void
    {
        // 'fr-BE' has no exact match, no normalization rule → primary code 'fr' matches
        self::assertSame('fr', $this->service->getBestAvailableLanguage(['fr-BE'], ['fr', 'de']));
    }

    public function testGetBestAvailableLanguageFallsBackToPrimaryCodeFromFullRegionCode(): void
    {
        // 'fr-FR' → exact 'fr-fr' not available → primary 'fr' matches
        self::assertSame('fr', $this->service->getBestAvailableLanguage(['fr-FR'], ['fr', 'de']));
    }

    public function testGetBestAvailableLanguageReturnsNullWhenPrimaryCodeNotAvailable(): void
    {
        // 'fr-BE' → primary 'fr', but only 'de' and original 'en' are available
        self::assertNull($this->service->getBestAvailableLanguage(['fr-BE'], ['de']));
    }

    public function testGetBestAvailableLanguageNormalizesNorwegianNnNo(): void
    {
        // nn-NO is an exception: normalized to 'no'
        self::assertSame('no', $this->service->getBestAvailableLanguage(['nn-NO'], ['no', 'fr']));
    }

    public function testGetBestAvailableLanguageNormalizesNorwegianNb(): void
    {
        // nb (Bokmål) is also normalized to 'no'
        self::assertSame('no', $this->service->getBestAvailableLanguage(['nb'], ['no', 'fr']));
    }

    public function testGetBestAvailableLanguageNormalizesChineseSimplified(): void
    {
        // zh-Hans → 'zh' (simplified Chinese exception)
        self::assertSame('zh', $this->service->getBestAvailableLanguage(['zh-Hans'], ['zh', 'fr']));
    }

    public function testGetBestAvailableLanguageNormalizesChineseTraditionalHk(): void
    {
        // zh-HK → 'zh-tw' (traditional Chinese exception covers HK/TW/MO)
        self::assertSame('zh-tw', $this->service->getBestAvailableLanguage(['zh-HK'], ['zh-TW', 'fr']));
    }

    public function testGetBestAvailableLanguageRespectsNavigatorPriority(): void
    {
        // Both 'de' and 'fr' are available; 'de' appears first in navigator → wins
        self::assertSame('de', $this->service->getBestAvailableLanguage(['de', 'fr'], ['fr', 'de']));
    }
}
