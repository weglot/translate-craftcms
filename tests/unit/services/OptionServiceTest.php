<?php

declare(strict_types=1);

namespace weglot\craftweglot\tests\unit\services;

use PHPUnit\Framework\TestCase;
use weglot\craftweglot\Plugin;
use weglot\craftweglot\services\LanguageService;
use weglot\craftweglot\services\OptionService;
use weglot\craftweglot\services\UserApiService;
use Weglot\Vendor\Weglot\Client\Api\LanguageEntry;
use Weglot\Vendor\Weglot\Util\Regex;

final class OptionServiceTest extends TestCase
{
    private string $savedApiKey = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->savedApiKey = Plugin::getInstance()->getTypedSettings()->apiKey;
        \Craft::$app->getCache()->flush();
    }

    protected function tearDown(): void
    {
        Plugin::getInstance()->getTypedSettings()->apiKey = $this->savedApiKey;
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
     * Build an OptionService whose API call is stubbed, so the real getOptions()
     * merge against the defaults is exercised.
     *
     * @param array<string,mixed> $apiResult
     */
    private function makeApiSvc(array $apiResult): OptionService
    {
        Plugin::getInstance()->getTypedSettings()->apiKey = 'sk_test';

        return new class($apiResult) extends OptionService {
            /** @param array<string,mixed> $apiResult */
            public function __construct(private readonly array $apiResult)
            {
                parent::__construct();
            }

            /** @return array{success: true, result: array<string, mixed>} */
            public function getOptionsFromApiWithApiKey(string $apiKey): array
            {
                return ['success' => true, 'result' => $this->apiResult];
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

    public function testGetTranslationEngineFallsBackToThreeWhenTheApiOmitsIt(): void
    {
        // V2 project settings never carry `translation_engine`; the WordPress plugin
        // falls back to 3 in the same situation.
        self::assertSame(3, $this->makeSvc()->getTranslationEngine());
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

    public function testGetPublicApiKeyReadsTheV2PublicKeyField(): void
    {
        // V2 project settings carry `public_key` and no `api_key` at all.
        $svc = $this->makeSvc(['public_key' => 'pk_live_abc123']);

        self::assertSame('pk_live_abc123', $svc->getPublicApiKey());
    }

    public function testGetPublicApiKeyFallsBackToApiKeyWhenPublicKeyIsBlank(): void
    {
        $svc = $this->makeSvc(['public_key' => '', 'api_key' => 'wg_live_abc123']);

        self::assertSame('wg_live_abc123', $svc->getPublicApiKey());
    }

    // -------------------------------------------------------------------------
    // getOptions — merge against the defaults
    // -------------------------------------------------------------------------

    public function testEmptyCustomSettingsFromApiKeepsTheNestedDefaults(): void
    {
        // V2 project settings always return `custom_settings` as an empty object.
        $customSettings = $this->makeApiSvc(['custom_settings' => []])->getOption('custom_settings');

        self::assertIsArray($customSettings);
        self::assertArrayHasKey('button_style', $customSettings);
        self::assertArrayHasKey('ai_disclaimer_selector', $customSettings);
    }

    public function testCustomSettingsFromApiOverrideTheDefaultsWithoutDroppingThem(): void
    {
        $customSettings = $this->makeApiSvc([
            'custom_settings' => ['translate_search' => true],
        ])->getOption('custom_settings');

        self::assertIsArray($customSettings);
        self::assertTrue($customSettings['translate_search']);
        self::assertArrayHasKey('button_style', $customSettings);
    }

    public function testTopLevelApiValuesStillOverrideTheDefaults(): void
    {
        $svc = $this->makeApiSvc(['language_from' => 'fr', 'media_enabled' => true]);

        self::assertSame('fr', $svc->getOption('language_from'));
        self::assertTrue($svc->getOption('media_enabled'));
    }

    // -------------------------------------------------------------------------
    // resetOptions
    // -------------------------------------------------------------------------

    public function testResetOptionsClearsTheWorkspaceSlugCache(): void
    {
        Plugin::getInstance()->getTypedSettings()->apiKey = 'sk_abc123';
        $cacheKey = UserApiService::workspaceCacheKey('sk_abc123');
        \Craft::$app->getCache()->set($cacheKey, 'my-workspace');

        (new OptionService())->resetOptions();

        self::assertFalse(\Craft::$app->getCache()->get($cacheKey));
    }
}
