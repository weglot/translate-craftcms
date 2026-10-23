<?php

declare(strict_types=1);

namespace weglot\craftweglot\tests\unit\services;

use craft\base\Component;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use weglot\craftweglot\helpers\HelperApi;
use weglot\craftweglot\Plugin;
use weglot\craftweglot\services\LanguageService;
use weglot\craftweglot\services\OptionService;
use weglot\craftweglot\services\SlugV2Service;
use Weglot\Vendor\Weglot\Client\Api\LanguageEntry;
use yii\mutex\Mutex;

final class SlugV2ServiceTest extends TestCase
{
    private const API_KEY = 'private-v2-key';
    private const API_BASE_URL = 'https://eu.api.weglot.test';
    private const REPLACED_COMPONENTS = ['option', 'language'];

    /** @var array<string, Component> */
    private array $originalComponents = [];

    private ?Mutex $originalMutex = null;

    /** @var array<string, mixed> */
    private array $options = [];

    /** @var list<LanguageEntry> */
    private array $languages = [];

    /** @var list<array{request: RequestInterface, options: array<string, mixed>}> */
    private array $requests = [];

    /** @var array{held: bool, timeouts: list<int>, released: int} */
    private array $lock = ['held' => false, 'timeouts' => [], 'released' => 0];

    protected function setUp(): void
    {
        parent::setUp();
        foreach (self::REPLACED_COMPONENTS as $id) {
            $component = Plugin::getInstance()->get($id);
            if ($component instanceof Component) {
                $this->originalComponents[$id] = $component;
            }
        }
        $this->originalMutex = \Craft::$app->getMutex();
        \Craft::$app->getCache()->flush();

        $this->options = [
            'auto_slug_translation_enabled' => true,
            'api_base_url' => self::API_BASE_URL,
            'versions' => ['slugTranslations' => 1000],
        ];
        $this->languages = [
            new LanguageEntry('fr', 'fr', 'French', 'Français', false),
        ];
        $this->stubComponents();
    }

    protected function tearDown(): void
    {
        // Guarded: setUp() may have thrown before every component was captured.
        foreach ($this->originalComponents as $id => $component) {
            Plugin::getInstance()->set($id, $component);
        }
        if ($this->originalMutex instanceof Mutex) {
            \Craft::$app->set('mutex', $this->originalMutex);
        }
        \Craft::$app->getCache()->flush();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Fetching
    // -------------------------------------------------------------------------

    public function testFetchesSlugsAndReversesThem(): void
    {
        $maps = $this->service([$this->ok(['guestbook' => 'livre-de-bronze'])])->getSlugMaps(self::API_KEY, ['fr']);

        self::assertSame(['guestbook' => 'livre-de-bronze'], $maps['fr']['forward']);
        self::assertSame(['livre-de-bronze' => 'guestbook'], $maps['fr']['reverse']);
    }

    public function testSendsOneRequestPerLanguageWithTheKeyHeaderAndTheInternalCode(): void
    {
        $this->languages[] = new LanguageEntry('de', 'de', 'German', 'Deutsch', false);

        $this->service([$this->ok(['guestbook' => 'livre-de-bronze']), $this->ok(['guestbook' => 'gaestebuch'])])
            ->getSlugMaps(self::API_KEY, ['fr', 'de']);

        self::assertCount(2, $this->requests);
        foreach (['fr', 'de'] as $i => $code) {
            $request = $this->requests[$i]['request'];
            self::assertSame('GET', $request->getMethod());
            self::assertSame(self::API_BASE_URL.SlugV2Service::ROUTE.'?language='.$code, (string) $request->getUri());
            self::assertSame('Key '.self::API_KEY, $request->getHeaderLine('Authorization'));
            self::assertLessThanOrEqual(3.0, $this->requests[$i]['options']['timeout']);
        }
    }

    public function testCustomExternalCodeSendsTheInternalCode(): void
    {
        $this->languages = [new LanguageEntry('pt-br', 'br', 'Portuguese (Brazil)', 'Português', false)];

        $maps = $this->service([$this->ok(['about' => 'sobre'])])->getSlugMaps(self::API_KEY, ['br']);

        self::assertSame('language=pt-br', $this->requests[0]['request']->getUri()->getQuery());
        self::assertSame(['sobre' => 'about'], $maps['br']['reverse']);
    }

    public function testMixedCaseExternalCodeIsMatched(): void
    {
        $this->languages = [new LanguageEntry('pt-br', 'pt-BR', 'Portuguese (Brazil)', 'Português', false)];

        $maps = $this->service([$this->ok(['about' => 'sobre'])])->getSlugMaps(self::API_KEY, ['pt-BR']);

        self::assertSame(['sobre' => 'about'], $maps['pt-br']['reverse']);
    }

    public function testNothingIsRequestedWhenTheFeatureIsOff(): void
    {
        $this->options['auto_slug_translation_enabled'] = false;
        self::assertSame([], $this->service([])->getSlugMaps(self::API_KEY, ['fr']));

        unset($this->options['auto_slug_translation_enabled']);
        self::assertSame([], $this->service([])->getSlugMaps(self::API_KEY, ['fr']));

        self::assertSame([], $this->requests);
    }

    public function testNothingIsRequestedForAWeglot1Key(): void
    {
        self::assertSame([], $this->service([])->getSlugMaps('wg_v1key', ['fr']));
        self::assertSame([], $this->requests);
    }

    public function testFallsBackToTheDefaultWeglot2HostWithoutApiBaseUrl(): void
    {
        unset($this->options['api_base_url']);

        $this->service([$this->ok(['guestbook' => 'livre-de-bronze'])])->getSlugMaps(self::API_KEY, ['fr']);

        self::assertStringStartsWith(HelperApi::getApiUrlV2().SlugV2Service::ROUTE, (string) $this->requests[0]['request']->getUri());
    }

    public function testAmbiguousSlugsAreLeftOut(): void
    {
        $maps = $this->service([$this->ok([
            'about' => 'shared',   // shared by two pages
            'team' => 'shared',
            'news' => 'contact',   // another page's original slug
            'contact' => 'nous-contacter',
        ])])->getSlugMaps(self::API_KEY, ['fr']);

        self::assertSame(['nous-contacter' => 'contact'], $maps['fr']['reverse']);
        self::assertSame(['contact' => 'nous-contacter'], $maps['fr']['forward']);
    }

    public function testEmptyIdenticalAndMultiSegmentEntriesAreDropped(): void
    {
        $maps = $this->service([$this->ok([
            'empty' => '',
            'same' => 'same',
            'blog' => 'blog/fr',
            'about' => 'a-propos',
        ])])->getSlugMaps(self::API_KEY, ['fr']);

        self::assertSame(['a-propos' => 'about'], $maps['fr']['reverse']);
    }

    public function testRedirectsKeepOnlyFormerSlugsPointingToACurrentOne(): void
    {
        $maps = $this->service([$this->ok(
            ['guestbook' => 'livre-de-bronze', 'golden-book' => 'livre-d-or'],
            [
                'livre-d-or' => 'livre-de-bronze',      // now page B's current slug: B wins
                'livre-dargent' => 'livre-de-bronze',   // kept
                'ancien' => 'disparu',                  // target no longer current
            ]
        )])->getSlugMaps(self::API_KEY, ['fr']);

        self::assertSame(['livre-dargent' => 'livre-de-bronze'], $maps['fr']['redirects']);
    }

    // -------------------------------------------------------------------------
    // Cache
    // -------------------------------------------------------------------------

    public function testSameVersionMakesNoCall(): void
    {
        $this->service([$this->ok(['guestbook' => 'livre-de-bronze'])])->getSlugMaps(self::API_KEY, ['fr']);
        $this->requests = [];

        $maps = $this->service([])->getSlugMaps(self::API_KEY, ['fr']);

        self::assertSame([], $this->requests);
        self::assertSame(['livre-de-bronze' => 'guestbook'], $maps['fr']['reverse']);
    }

    public function testNewVersionRefetches(): void
    {
        $this->service([$this->ok(['guestbook' => 'livre-d-or'])])->getSlugMaps(self::API_KEY, ['fr']);
        $this->options['versions'] = ['slugTranslations' => 2000];

        $maps = $this->service([$this->ok(['guestbook' => 'livre-de-bronze'])])->getSlugMaps(self::API_KEY, ['fr']);

        self::assertCount(2, $this->requests);
        self::assertSame(['livre-de-bronze' => 'guestbook'], $maps['fr']['reverse']);
    }

    public function testSingularWeglot1VersionFieldIsIgnored(): void
    {
        $this->service([$this->ok(['guestbook' => 'livre-d-or'])])->getSlugMaps(self::API_KEY, ['fr']);
        $this->options['versions'] = ['slugTranslations' => 1000, 'slugTranslation' => 9999];

        $this->service([])->getSlugMaps(self::API_KEY, ['fr']);

        self::assertCount(1, $this->requests);
    }

    public function testEntryOlderThanADayIsRefetched(): void
    {
        $this->seedCache(self::API_KEY, ['fr' => $this->entry('1000', time() - 86401, ['guestbook' => 'livre-d-or'])]);

        $this->service([$this->ok(['guestbook' => 'livre-de-bronze'])])->getSlugMaps(self::API_KEY, ['fr']);

        self::assertCount(1, $this->requests);
    }

    public function testCacheOfAnotherApiKeyIsIgnored(): void
    {
        $this->seedCache('another-key', ['fr' => $this->entry('1000', time(), ['guestbook' => 'livre-d-or'])]);

        $maps = $this->service([$this->ok(['guestbook' => 'livre-de-bronze'])])->getSlugMaps(self::API_KEY, ['fr']);

        self::assertCount(1, $this->requests);
        self::assertSame(['livre-de-bronze' => 'guestbook'], $maps['fr']['reverse']);
    }

    public function testLockHeldElsewhereServesTheOldSlugsWithoutWaiting(): void
    {
        $this->seedCache(self::API_KEY, ['fr' => $this->entry('999', time(), ['guestbook' => 'livre-d-or'])]);
        $this->lock['held'] = true;

        $maps = $this->service([])->getSlugMaps(self::API_KEY, ['fr']);

        self::assertSame([], $this->requests);
        self::assertSame([0], $this->lock['timeouts']);
        self::assertSame(['livre-d-or' => 'guestbook'], $maps['fr']['reverse']);
    }

    public function testColdCacheWaitsForTheRequestHoldingTheLock(): void
    {
        $this->lock['held'] = true;

        $maps = $this->service([])->getSlugMaps(self::API_KEY, ['fr']);

        self::assertSame([], $this->requests);
        self::assertSame([5], $this->lock['timeouts']);
        self::assertSame([], $maps);
    }

    public function testLockIsReleasedAfterARefresh(): void
    {
        $this->service([$this->ok(['guestbook' => 'livre-de-bronze'])])->getSlugMaps(self::API_KEY, ['fr']);

        self::assertSame(1, $this->lock['released']);
    }

    public function testFailureKeepsThePreviousSlugsAndWaitsBeforeRetrying(): void
    {
        $this->seedCache(self::API_KEY, ['fr' => $this->entry('999', time(), ['guestbook' => 'livre-d-or'])]);

        $maps = $this->service([new Response(503)])->getSlugMaps(self::API_KEY, ['fr']);
        self::assertSame(['livre-d-or' => 'guestbook'], $maps['fr']['reverse']);

        $this->service([])->getSlugMaps(self::API_KEY, ['fr']);
        self::assertCount(1, $this->requests);
    }

    public function testNonJsonBodyCountsAsAFailure(): void
    {
        $this->seedCache(self::API_KEY, ['fr' => $this->entry('999', time(), ['guestbook' => 'livre-d-or'])]);

        $maps = $this->service([new Response(200, [], '<html>maintenance</html>')])->getSlugMaps(self::API_KEY, ['fr']);

        self::assertSame(['livre-d-or' => 'guestbook'], $maps['fr']['reverse']);
    }

    public function testOneFailingLanguageDoesNotRefetchTheOthers(): void
    {
        $this->languages[] = new LanguageEntry('de', 'de', 'German', 'Deutsch', false);

        $maps = $this->service([new Response(503), $this->ok(['guestbook' => 'gaestebuch'])])
            ->getSlugMaps(self::API_KEY, ['fr', 'de']);
        self::assertSame(['gaestebuch' => 'guestbook'], $maps['de']['reverse']);

        $this->service([])->getSlugMaps(self::API_KEY, ['fr', 'de']);
        self::assertCount(2, $this->requests);
    }

    public function testResetOptionsDropsTheCache(): void
    {
        $this->seedCache('wg_or_v2_seed', ['fr' => $this->entry('1000', time(), ['guestbook' => 'livre-d-or'])]);
        $settings = Plugin::getInstance()->getTypedSettings();
        $savedApiKey = $settings->apiKey;
        $settings->apiKey = 'wg_or_v2_seed';

        try {
            (new OptionService())->resetOptions();
        } finally {
            $settings->apiKey = $savedApiKey;
        }

        self::assertFalse(\Craft::$app->getCache()->get(SlugV2Service::cacheKey('wg_or_v2_seed')));
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function stubComponents(): void
    {
        $test = $this;

        Plugin::getInstance()->set('option', new class($test) extends OptionService {
            public function __construct(private readonly SlugV2ServiceTest $test)
            {
                parent::__construct();
            }

            public function getOption(string $key): string|array|int|bool|null
            {
                return $this->test->option($key);
            }
        });

        Plugin::getInstance()->set('language', new class($test) extends LanguageService {
            public function __construct(private readonly SlugV2ServiceTest $test)
            {
                parent::__construct();
            }

            public function getDestinationLanguages(): array
            {
                return $this->test->destinationLanguages();
            }
        });

        $lock = &$this->lock;
        \Craft::$app->set('mutex', new class($lock) extends Mutex {
            /** @param array{held: bool, timeouts: list<int>, released: int} $lock */
            public function __construct(private array &$lock)
            {
                parent::__construct();
            }

            /**
             * @param string $name
             * @param int    $timeout
             *
             * @phpstan-ignore-next-line typeCoverage.paramTypeCoverage
             */
            protected function acquireLock($name, $timeout = 0): bool
            {
                $this->lock['timeouts'][] = $timeout;

                return !$this->lock['held'];
            }

            /**
             * @param string $name
             *
             * @phpstan-ignore-next-line typeCoverage.paramTypeCoverage
             */
            protected function releaseLock($name): bool
            {
                ++$this->lock['released'];

                return true;
            }
        });
    }

    /**
     * @return string|array<mixed>|int|bool|null
     */
    public function option(string $key): string|array|int|bool|null
    {
        $value = $this->options[$key] ?? null;

        return \is_string($value) || \is_array($value) || \is_int($value) || \is_bool($value) ? $value : null;
    }

    /**
     * @return list<LanguageEntry>
     */
    public function destinationLanguages(): array
    {
        return $this->languages;
    }

    /**
     * A new service is a new request: nothing memoised.
     *
     * @param list<Response> $responses
     */
    private function service(array $responses): SlugV2Service
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->requests));

        return new class(new Client(['handler' => $stack])) extends SlugV2Service {
            public function __construct(private readonly ClientInterface $client)
            {
                parent::__construct();
            }

            protected function createHttpClient(): ClientInterface
            {
                return $this->client;
            }
        };
    }

    /**
     * @param array<string, string> $slugs
     * @param array<string, string> $redirects
     */
    private function ok(array $slugs, array $redirects = []): Response
    {
        return new Response(200, ['Content-Type' => 'application/json'], (string) json_encode([
            'slugs' => (object) $slugs,
            'redirects' => (object) $redirects,
        ]));
    }

    /**
     * @param array<string, string> $slugs
     *
     * @return array{version: string|null, fetched_at: int, retry_after: int, slugs: array<string, string>, redirects: array<string, string>}
     */
    private function entry(?string $version, int $fetchedAt, array $slugs): array
    {
        return ['version' => $version, 'fetched_at' => $fetchedAt, 'retry_after' => 0, 'slugs' => $slugs, 'redirects' => []];
    }

    /**
     * @param array<string, mixed> $entries
     */
    private function seedCache(string $apiKey, array $entries): void
    {
        \Craft::$app->getCache()->set(SlugV2Service::cacheKey($apiKey), $entries, 0);
    }
}
