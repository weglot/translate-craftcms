<?php

declare(strict_types=1);

namespace weglot\craftweglot\tests\unit\services;

use craft\base\Component;
use craft\web\View;
use PHPUnit\Framework\TestCase;
use weglot\craftweglot\Plugin;
use weglot\craftweglot\services\LanguageService;
use weglot\craftweglot\services\OptionService;
use weglot\craftweglot\services\RequestUrlService;
use weglot\craftweglot\services\SlugService;
use Weglot\Vendor\Weglot\Client\Api\LanguageEntry;
use Weglot\Vendor\Weglot\Util\Url;

final class OptionServiceSwitcherLinksTest extends TestCase
{
    private const REPLACED_COMPONENTS = ['requestUrlService', 'language', 'slug'];

    private LanguageEntry $en;
    private LanguageEntry $fr;

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
        \Craft::$app->getCache()->flush();

        $this->en = new LanguageEntry('en', 'en', 'English', 'English', false);
        $this->fr = new LanguageEntry('fr', 'fr', 'French', 'Français', false);
    }

    protected function tearDown(): void
    {
        // Guarded: setUp() may have thrown before every component was captured.
        foreach ($this->originalComponents as $id => $component) {
            Plugin::getInstance()->set($id, $component);
        }
        Plugin::getInstance()->getTypedSettings()->apiKey = $this->savedApiKey;
        \Craft::$app->getCache()->flush();
        parent::tearDown();
    }

    public function testSwitcherLinksDropTrackingParamsAndKeepTheOthers(): void
    {
        $links = $this->switcherLinksFor('https://example.com/page?page=2&gad_source=1&gad_campaignid=123', false);

        self::assertSame('https://example.com/page?page=2', $links['en']);
        self::assertSame('https://example.com/fr/page?page=2', $links['fr']);
    }

    public function testSwitcherLinksHaveNoQuestionMarkWhenOnlyTrackingParams(): void
    {
        $links = $this->switcherLinksFor('https://example.com/page?gclid=abc&utm_source=ads', false);

        self::assertSame('https://example.com/page', $links['en']);
        self::assertSame('https://example.com/fr/page', $links['fr']);
    }

    public function testAutoRedirectMarkerStartsTheQueryWhenOnlyTrackingParams(): void
    {
        $links = $this->switcherLinksFor('https://example.com/page?gclid=abc', true);

        self::assertSame('https://example.com/page?wg-choose-original=true', $links['en']);
        self::assertSame('https://example.com/fr/page?wg-choose-original=false', $links['fr']);
    }

    /**
     * @return array<string, string>
     */
    private function switcherLinksFor(string $requestUrl, bool $autoRedirect): array
    {
        $this->injectStubs($requestUrl);

        $service = new class($autoRedirect) extends OptionService {
            public function __construct(private readonly bool $autoRedirect)
            {
                parent::__construct();
            }

            /** @return array<string, mixed> */
            public function getOptions(): array
            {
                return array_merge($this->getOptionsDefault(), ['auto_redirect' => $this->autoRedirect]);
            }
        };

        $view = \Craft::$app->getView();
        $view->startHtmlBuffer();
        $service->generateWeglotData();
        $buffered = $view->clearHtmlBuffer();
        self::assertIsArray($buffered);
        self::assertArrayHasKey(View::POS_HEAD, $buffered);
        $html = implode('', $buffered[View::POS_HEAD]);

        self::assertSame(1, preg_match('#<script type="application/json" id="weglot-data">(.*)</script>#s', $html, $matches));
        $data = json_decode($matches[1], true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        self::assertIsArray($data['switcher_links']);
        // generateWeglotData() swallows a stub failure and leaves switcher_links empty.
        self::assertArrayHasKey('en', $data['switcher_links']);
        self::assertArrayHasKey('fr', $data['switcher_links']);

        /** @var array<string, string> $links */
        $links = $data['switcher_links'];

        return $links;
    }

    private function injectStubs(string $requestUrl): void
    {
        $url = new Url($requestUrl, $this->en, [$this->fr], '', [], []);

        Plugin::getInstance()->set('requestUrlService', new class($url) extends RequestUrlService {
            public function __construct(private readonly Url $url)
            {
                parent::__construct();
            }

            public function getWeglotUrl(): Url
            {
                return $this->url;
            }
        });

        Plugin::getInstance()->set('language', new class($this->en, $this->fr) extends LanguageService {
            public function __construct(
                private readonly LanguageEntry $original,
                private readonly LanguageEntry $destination,
            ) {
                parent::__construct();
            }

            public function getOriginalLanguage(): LanguageEntry
            {
                return $this->original;
            }

            /** @return list<LanguageEntry> */
            public function getDestinationLanguages(): array
            {
                return [$this->destination];
            }
        });

        // No slug mapping: keeps translateSourcePathForLanguage() off the network.
        Plugin::getInstance()->set('slug', new class extends SlugService {
            public function getRedirectPathIfUntranslated(string $apiKey, array $destinationLanguages, string $languageTo, string $pathWithoutLangPrefix): ?string
            {
                return null;
            }
        });
    }
}
