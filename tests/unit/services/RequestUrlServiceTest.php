<?php

declare(strict_types=1);

namespace weglot\craftweglot\tests\unit\services;

use craft\base\Component;
use PHPUnit\Framework\TestCase;
use weglot\craftweglot\Plugin;
use weglot\craftweglot\services\LanguageService;
use weglot\craftweglot\services\OptionService;
use weglot\craftweglot\services\RequestUrlService;
use weglot\craftweglot\services\SlugService;
use Weglot\Vendor\Weglot\Client\Api\LanguageEntry;
use Weglot\Vendor\Weglot\Util\Regex;
use Weglot\Vendor\Weglot\Util\Regex\RegexEnum;
use Weglot\Vendor\Weglot\Util\Url;

final class RequestUrlServiceTest extends TestCase
{
    private const REPLACED_COMPONENTS = ['language', 'option', 'slug'];

    private LanguageEntry $en;
    private LanguageEntry $fr;
    private LanguageEntry $it;

    /** @var array<string, Component> */
    private array $originalComponents = [];

    private string $savedApiKey = '';

    private bool $savedEnableTranslatedSlugs = true;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (self::REPLACED_COMPONENTS as $id) {
            $component = Plugin::getInstance()->get($id);
            if ($component instanceof Component) {
                $this->originalComponents[$id] = $component;
            }
        }
        $settings = Plugin::getInstance()->getTypedSettings();
        $this->savedApiKey = $settings->apiKey;
        $this->savedEnableTranslatedSlugs = $settings->enableTranslatedSlugs;
        \Craft::$app->getCache()->flush();

        $this->en = new LanguageEntry('en', 'en', 'English', 'English', false);
        $this->fr = new LanguageEntry('fr', 'fr', 'French', 'Français', false);
        $this->it = new LanguageEntry('it', 'it', 'Italian', 'Italiano', false);
    }

    protected function tearDown(): void
    {
        // Guarded: setUp() may have thrown before every component was captured.
        foreach ($this->originalComponents as $id => $component) {
            Plugin::getInstance()->set($id, $component);
        }
        $settings = Plugin::getInstance()->getTypedSettings();
        $settings->apiKey = $this->savedApiKey;
        $settings->enableTranslatedSlugs = $this->savedEnableTranslatedSlugs;
        \Craft::$app->getCache()->flush();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Stub factories
    // -------------------------------------------------------------------------

    /**
     * Inject a LanguageService stub that returns controlled original and
     * destination languages, bypassing all API calls.
     *
     * @param LanguageEntry[] $destinations
     */
    private function injectLangStub(LanguageEntry $original, array $destinations = []): void
    {
        $stub = new class($original, $destinations) extends LanguageService {
            /** @param LanguageEntry[] $destinations */
            public function __construct(
                private readonly LanguageEntry $original,
                private readonly array $destinations,
            ) {
                parent::__construct();
            }

            public function getOriginalLanguage(): LanguageEntry
            {
                return $this->original;
            }

            /** @return LanguageEntry[] */
            public function getDestinationLanguages(): array
            {
                return $this->destinations;
            }
        };
        Plugin::getInstance()->set('language', $stub);
    }

    /**
     * Inject an OptionService stub that returns the given exclude-URL list
     * and null for all other options.
     *
     * @param array<int, mixed> $excludeUrls
     */
    private function injectOptionStub(array $excludeUrls = []): void
    {
        $stub = new class($excludeUrls) extends OptionService {
            /** @param array<int, mixed> $excludeUrls */
            public function __construct(private readonly array $excludeUrls)
            {
                parent::__construct();
            }

            /** @return array<int, mixed> */
            public function getExcludeUrls(): array
            {
                return $this->excludeUrls;
            }

            public function getOption(string $key): string|array|null
            {
                return null;
            }
        };
        Plugin::getInstance()->set('option', $stub);
    }

    /**
     * Build a RequestUrlService subclass whose getWeglotUrl() returns a
     * controlled Url stub — lets us test handlePathDetectionAndRewrite()
     * without a live Craft request.
     */
    private function makeHandlePathSvc(
        LanguageEntry $current,
        LanguageEntry $original,
        string $urlPath,
    ): RequestUrlService {
        $urlStub = new class($current, $original, $urlPath) extends Url {
            public function __construct(
                private readonly LanguageEntry $currentLang,
                private readonly LanguageEntry $defaultLang,
                private readonly string $urlPath,
            ) {
                parent::__construct('https://example.com', $defaultLang, [], '', [], []);
            }

            public function getCurrentLanguage(): LanguageEntry
            {
                return $this->currentLang;
            }

            public function getDefault(): LanguageEntry
            {
                return $this->defaultLang;
            }

            public function getPath(): string
            {
                return $this->urlPath;
            }
        };

        return new class($urlStub) extends RequestUrlService {
            public function __construct(private readonly Url $stub)
            {
                parent::__construct();
            }

            public function getWeglotUrl(): Url
            {
                return $this->stub;
            }
        };
    }

    // -------------------------------------------------------------------------
    // createUrlObject
    // -------------------------------------------------------------------------

    public function testCreateUrlObjectReturnsUrlInstance(): void
    {
        $this->injectLangStub($this->en, [$this->fr]);
        $this->injectOptionStub();

        $svc = new RequestUrlService();
        $url = $svc->createUrlObject('https://example.com/page');

        self::assertInstanceOf(Url::class, $url);
    }

    public function testCreateUrlObjectDetectsCurrentLanguageFromUrlPrefix(): void
    {
        $this->injectLangStub($this->en, [$this->fr]);
        $this->injectOptionStub();

        $svc = new RequestUrlService();
        $url = $svc->createUrlObject('https://example.com/fr/about');

        self::assertSame('fr', $url->getCurrentLanguage()->getInternalCode());
    }

    // -------------------------------------------------------------------------
    // isEligibleUrl
    // -------------------------------------------------------------------------

    public function testIsEligibleUrlReturnsDestinationLanguagesForTranslatablePage(): void
    {
        $this->injectLangStub($this->en, [$this->fr]);
        $this->injectOptionStub();

        $svc = new RequestUrlService();
        $result = $svc->isEligibleUrl('https://example.com/about');

        self::assertCount(1, $result);
        self::assertSame('fr', $result[0]->getInternalCode());
    }

    public function testIsEligibleUrlReturnsEmptyArrayWhenNoDestinationLanguages(): void
    {
        $this->injectLangStub($this->en, []);
        $this->injectOptionStub();

        $svc = new RequestUrlService();

        self::assertSame([], $svc->isEligibleUrl('https://example.com/about'));
    }

    public function testIsEligibleUrlReturnsEmptyArrayForExcludedUrl(): void
    {
        $this->injectLangStub($this->en, [$this->fr]);
        // Exclude any URL that starts with /actions/
        $this->injectOptionStub([
            [new Regex(RegexEnum::START_WITH, '/actions/'), null],
        ]);

        $svc = new RequestUrlService();

        self::assertSame([], $svc->isEligibleUrl('https://example.com/actions/my-action'));
    }

    // -------------------------------------------------------------------------
    // handlePathDetectionAndRewrite
    // -------------------------------------------------------------------------

    public function testHandlePathReturnsPathUnchangedWhenCurrentLanguageIsOriginal(): void
    {
        $svc = $this->makeHandlePathSvc($this->en, $this->en, '/about');

        self::assertSame('/about', $svc->handlePathDetectionAndRewrite('/about'));
    }

    public function testHandlePathReturnsSlashWhenPathIsJustLanguageExternalCode(): void
    {
        // e.g. the user visits /fr — the canonical path is just '/'
        $svc = $this->makeHandlePathSvc($this->fr, $this->en, '/');

        self::assertSame('/', $svc->handlePathDetectionAndRewrite('fr'));
    }

    public function testHandlePathReturnsInternalUrlPathOnTranslatedPage(): void
    {
        // On /fr/about the internal path is /about; the method returns getPath()
        $svc = $this->makeHandlePathSvc($this->fr, $this->en, '/about');

        self::assertSame('/about', $svc->handlePathDetectionAndRewrite('/fr/about'));
    }

    // -------------------------------------------------------------------------
    // getSourceUrl / getTranslationRequestUrl
    // -------------------------------------------------------------------------

    public function testTranslatedPageSendsTheSourceSlugsWithTheQuery(): void
    {
        $svc = $this->makeSourceUrlSvc('https://example.com/it/blog-it/libro-degli-ospiti/?page=2', [
            'blog' => 'blog-it',
            'guestbook' => 'libro-degli-ospiti',
        ]);

        self::assertSame('https://example.com/blog/guestbook/?page=2', $svc->getTranslationRequestUrl(200));
    }

    public function testNonAsciiSourceSlugIsPercentEncodedLikeASourceRequest(): void
    {
        $svc = $this->makeSourceUrlSvc('https://example.com/it/caffe/', ['café' => 'caffe']);

        self::assertSame('https://example.com/caf%C3%A9/', $svc->getSourceUrl());
    }

    public function testTranslatedPageWithoutSlugMappingSendsItsPathWithoutLanguagePrefix(): void
    {
        $svc = $this->makeSourceUrlSvc('https://example.com/it/about', []);

        self::assertSame('https://example.com/about', $svc->getTranslationRequestUrl(200));
    }

    public function testDisabledTranslatedSlugsKeepTheRequestedSlug(): void
    {
        $svc = $this->makeSourceUrlSvc('https://example.com/it/libro-degli-ospiti/', ['guestbook' => 'libro-degli-ospiti']);
        Plugin::getInstance()->getTypedSettings()->enableTranslatedSlugs = false;

        self::assertSame('https://example.com/libro-degli-ospiti/', $svc->getSourceUrl());
    }

    public function testSourceLanguagePageKeepsTheParserDefault(): void
    {
        $svc = $this->makeSourceUrlSvc('https://example.com/guestbook/?page=2', ['guestbook' => 'libro-degli-ospiti']);

        self::assertSame('', $svc->getTranslationRequestUrl(200));
        self::assertSame('https://example.com/guestbook/?page=2', $svc->getSourceUrl());
    }

    public function testErrorPageSendsTheShared404Url(): void
    {
        $svc = $this->makeSourceUrlSvc('https://example.com/it/libro-degli-ospiti-typo/', ['guestbook' => 'libro-degli-ospiti']);

        self::assertSame('https://example.com/404', $svc->getTranslationRequestUrl(404));
    }

    /**
     * A RequestUrlService on a fixed URL, with a real SlugService reading Weglot 1 slug maps
     * seeded in the cache, so the enableTranslatedSlugs gate stays in the path.
     *
     * @param array<string, string> $forward source slug => Italian slug
     */
    private function makeSourceUrlSvc(string $requestUrl, array $forward): RequestUrlService
    {
        $this->injectLangStub($this->en, [$this->it]);
        Plugin::getInstance()->getTypedSettings()->apiKey = 'wg_test';

        $slug = new SlugService();
        $cacheKey = (new \ReflectionMethod(SlugService::class, 'buildSlugsCacheKey'))->invoke($slug, 'wg_test', ['it']);
        \Craft::$app->getCache()->set($cacheKey, ['it' => ['forward' => $forward, 'reverse' => array_flip($forward)]]);
        Plugin::getInstance()->set('slug', $slug);

        $url = new Url($requestUrl, $this->en, [$this->it], '', [], []);

        return new class($url) extends RequestUrlService {
            public function __construct(private readonly Url $url)
            {
                parent::__construct();
            }

            public function getWeglotUrl(): Url
            {
                return $this->url;
            }
        };
    }
}
