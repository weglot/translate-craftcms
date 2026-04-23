<?php

declare(strict_types=1);

namespace weglot\craftweglot\tests\unit\services;

use PHPUnit\Framework\TestCase;
use weglot\craftweglot\Plugin;
use weglot\craftweglot\services\LanguageService;
use weglot\craftweglot\services\OptionService;
use Weglot\Vendor\Weglot\Client\Api\LanguageEntry;
use Weglot\Vendor\Weglot\Util\Regex;

final class OptionServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        \Craft::$app->getCache()->flush();
    }

    protected function tearDown(): void
    {
        \Craft::$app->getCache()->flush();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Fixtures & factories
    // -------------------------------------------------------------------------

    /**
     * Build an OptionService stub whose getOptions() returns the default options
     * merged with $overrides — bypassing all API and cache calls.
     *
     * @param array<string,mixed> $overrides
     */
    private function makeSvc(array $overrides = []): OptionService
    {
        return new class($overrides) extends OptionService {
            /** @param array<string,mixed> $overrides */
            public function __construct(private readonly array $overrides)
            {
                parent::__construct();
            }

            /** @return array<string,mixed> */
            public function getOptions(): array
            {
                return array_merge($this->getOptionsDefault(), $this->overrides);
            }
        };
    }

    /**
     * Inject a minimal LanguageService stub so that getExcludeUrls() does not
     * attempt real API calls when it reads Plugin::getInstance()->getLanguage().
     */
    private function injectLangStub(): void
    {
        $stub = new class extends LanguageService {
            public function getOriginalLanguage(): LanguageEntry
            {
                return new LanguageEntry('en', 'en', 'English', 'English', false);
            }
        };
        Plugin::getInstance()->set('language', $stub);
    }

    // -------------------------------------------------------------------------
    // getExcludeBlocks
    // -------------------------------------------------------------------------

    public function testGetExcludeBlocksContainsAllHardcodedEntries(): void
    {
        $svc = $this->makeSvc();
        $blocks = $svc->getExcludeBlocks();

        foreach (['.menu-item-weglot a', '.material-icons', '.fas', '.far', '.fad', '#yii-debug-toolbar'] as $expected) {
            self::assertContains($expected, $blocks);
        }
    }

    public function testGetExcludeBlocksIncludesCustomBlockFromArrayValueFormat(): void
    {
        $svc = $this->makeSvc(['excluded_blocks' => [['value' => '.my-custom-class']]]);

        self::assertContains('.my-custom-class', $svc->getExcludeBlocks());
    }

    public function testGetExcludeBlocksIncludesCustomBlockFromStringFormat(): void
    {
        $svc = $this->makeSvc(['excluded_blocks' => ['.string-block']]);

        self::assertContains('.string-block', $svc->getExcludeBlocks());
    }

    public function testGetExcludeBlocksDeduplicatesDuplicatedHardcodedEntry(): void
    {
        // Supplying a hardcoded entry via options must not create a second copy.
        $svc = $this->makeSvc(['excluded_blocks' => ['.material-icons']]);

        self::assertSame(1, substr_count(implode(',', $svc->getExcludeBlocks()), '.material-icons'));
    }

    // -------------------------------------------------------------------------
    // getExcludeUrls
    // -------------------------------------------------------------------------

    public function testGetExcludeUrlsMatchesActionsUrls(): void
    {
        $this->injectLangStub();
        $entries = $this->makeSvc()->getExcludeUrls();

        $found = false;
        foreach ($entries as $entry) {
            if (\is_array($entry) && $entry[0] instanceof Regex && $entry[0]->match('/actions/my-action')) {
                $found = true;
                break;
            }
        }

        self::assertTrue($found);
    }

    public function testGetExcludeUrlsMatchesIndexPhpActionsUrls(): void
    {
        $this->injectLangStub();
        $entries = $this->makeSvc()->getExcludeUrls();

        $found = false;
        foreach ($entries as $entry) {
            if (\is_array($entry) && $entry[0] instanceof Regex && $entry[0]->match('/index.php/actions/my-action')) {
                $found = true;
                break;
            }
        }

        self::assertTrue($found);
    }

    public function testGetExcludeUrlsMatchesSitemapXml(): void
    {
        $this->injectLangStub();
        $entries = $this->makeSvc()->getExcludeUrls();

        $found = false;
        foreach ($entries as $entry) {
            if (\is_array($entry) && $entry[0] instanceof Regex && $entry[0]->match('/sitemap.xml')) {
                $found = true;
                break;
            }
        }

        self::assertTrue($found);
    }

    public function testGetExcludeUrlsEachEntryHasRegexAtIndexZero(): void
    {
        $this->injectLangStub();
        $entries = $this->makeSvc()->getExcludeUrls();

        self::assertNotEmpty($entries);
        foreach ($entries as $entry) {
            self::assertIsArray($entry);
            self::assertInstanceOf(Regex::class, $entry[0]);
        }
    }

    // -------------------------------------------------------------------------
    // saveWeglotSettings — early exits (no HTTP required)
    // -------------------------------------------------------------------------

    public function testSaveWeglotSettingsReturnsFalseWhenCdnFetchFails(): void
    {
        $svc = new class extends OptionService {
            /** @return array{success: true, result: array<string, mixed>}|array{success: false, result: array<string, mixed>} */
            public function getOptionsFromApiWithApiKey(string $apiKey): array
            {
                return ['success' => false, 'result' => []];
            }
        };

        $result = $svc->saveWeglotSettings('wg_test_key', 'en', ['fr']);

        self::assertFalse($result['success']);
        self::assertSame('cdn_fetch_fail', $result['code'] ?? null);
    }

    public function testSaveWeglotSettingsReturnsFalseWhenApiKeyMissingFromOptions(): void
    {
        $svc = new class extends OptionService {
            /** @return array{success: true, result: array<string, mixed>}|array{success: false, result: array<string, mixed>} */
            public function getOptionsFromApiWithApiKey(string $apiKey): array
            {
                return ['success' => true, 'result' => ['api_key' => '']];
            }
        };

        $result = $svc->saveWeglotSettings('wg_test_key', 'en', ['fr']);

        self::assertFalse($result['success']);
        self::assertSame('missing_private_key', $result['code'] ?? null);
    }

    // -------------------------------------------------------------------------
    // getTranslationEngine
    // -------------------------------------------------------------------------

    public function testGetTranslationEngineReturnsDefaultValueOfTwo(): void
    {
        // Default options have 'translation_engine' => 2
        self::assertSame(2, $this->makeSvc()->getTranslationEngine());
    }

    public function testGetTranslationEngineReturnsConfiguredValue(): void
    {
        self::assertSame(5, $this->makeSvc(['translation_engine' => 5])->getTranslationEngine());
    }

    // -------------------------------------------------------------------------
    // getPublicApiKey
    // -------------------------------------------------------------------------

    public function testGetPublicApiKeyReturnsEmptyStringWhenApiKeyOptionIsEmpty(): void
    {
        // Default options have 'api_key' => '' → method must return ''
        self::assertSame('', $this->makeSvc()->getPublicApiKey());
    }

    public function testGetPublicApiKeyReturnsValueFromOptions(): void
    {
        $svc = $this->makeSvc(['api_key' => 'wg_live_abc123']);

        self::assertSame('wg_live_abc123', $svc->getPublicApiKey());
    }
}
