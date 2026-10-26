<?php

declare(strict_types=1);

namespace weglot\craftweglot\tests\unit\services;

use craft\base\Component;
use PHPUnit\Framework\TestCase;
use weglot\craftweglot\models\Settings;
use weglot\craftweglot\Plugin;
use weglot\craftweglot\services\SlugService;
use weglot\craftweglot\services\SlugV2Service;

final class SlugServiceWeglot2Test extends TestCase
{
    private const V2_KEY = 'private-v2-key';

    private ?Component $originalSlugV2 = null;

    private int $weglot2Calls = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalSlugV2 = Plugin::getInstance()->getSlugV2();
        \Craft::$app->getCache()->flush();

        $calls = &$this->weglot2Calls;
        Plugin::getInstance()->set('slugV2', new class($calls) extends SlugV2Service {
            public function __construct(private int &$calls)
            {
                parent::__construct();
            }

            public function getSlugMaps(string $apiKey, array $externalCodes): array
            {
                ++$this->calls;

                return [
                    'fr' => [
                        'forward' => ['guestbook' => 'livre-de-bronze', 'contact' => 'contact-c-fr', 'blog' => 'blog-fr'],
                        'reverse' => ['livre-de-bronze' => 'guestbook', 'contact-c-fr' => 'contact', 'blog-fr' => 'blog'],
                        'redirects' => [
                            'livre-dargent' => 'livre-de-bronze',
                            'livre-d’argent-é' => 'livre-de-bronze',
                            // former slug of the guestbook, and original slug of the contact page
                            'contact' => 'livre-de-bronze',
                        ],
                    ],
                ];
            }
        });
    }

    protected function tearDown(): void
    {
        // Guarded: setUp() may have thrown before the component was captured.
        if ($this->originalSlugV2 instanceof SlugV2Service) {
            Plugin::getInstance()->set('slugV2', $this->originalSlugV2);
        }
        Plugin::getInstance()->getTypedSettings()->enableTranslatedSlugs = true;
        \Craft::$app->getCache()->flush();
        parent::tearDown();
    }

    public function testWeglot2KeyUsesOnlyTheWeglot2SlugsAndIgnoresLeftoverWeglot1Ones(): void
    {
        $service = new SlugService();
        $leftover = ['fr' => ['forward' => ['guestbook' => 'livre-v1'], 'reverse' => ['livre-v1' => 'guestbook']]];
        foreach ([self::V2_KEY, 'wg_v1key'] as $apiKey) {
            \Craft::$app->getCache()->set($this->weglot1CacheKey($service, $apiKey), $leftover, 0);
        }

        self::assertSame('guestbook', $service->getInternalPathIfTranslatedSlug(self::V2_KEY, ['fr'], 'fr', 'livre-de-bronze'));
        self::assertNull($service->getInternalPathIfTranslatedSlug(self::V2_KEY, ['fr'], 'fr', 'livre-v1'));
        // The seeded Weglot 1 cache is the one a Weglot 1 key reads.
        self::assertSame('guestbook', $service->getInternalPathIfTranslatedSlug('wg_v1key', ['fr'], 'fr', 'livre-v1'));
    }

    public function testFormerSlugRedirectsToTheCurrentOneKeepingTheOtherSegments(): void
    {
        self::assertSame(
            'blog-fr/livre-de-bronze',
            (new SlugService())->getRedirectPathIfFormerSlug(self::V2_KEY, ['fr'], 'fr', 'blog-fr/livre-dargent')
        );
    }

    public function testNonAsciiFormerSlugIsRedirected(): void
    {
        self::assertSame('livre-de-bronze', (new SlugService())->getRedirectPathIfFormerSlug(self::V2_KEY, ['fr'], 'fr', 'livre-d’argent-é'));
    }

    public function testCurrentSlugIsNotRedirected(): void
    {
        self::assertNull((new SlugService())->getRedirectPathIfFormerSlug(self::V2_KEY, ['fr'], 'fr', 'livre-de-bronze'));
    }

    /**
     * `contact` is both a former slug of the guestbook and the original slug of the contact page.
     * The live page must be served: matching the history on the path mapped back to original slugs
     * would send `/fr/contact-c-fr` to the guestbook.
     */
    public function testLivePageWhoseOriginalSlugIsAFormerSlugIsServed(): void
    {
        $service = new SlugService();

        self::assertNull($service->getRedirectPathIfUntranslated(self::V2_KEY, ['fr'], 'fr', 'contact-c-fr'));
        self::assertNull($service->getRedirectPathIfFormerSlug(self::V2_KEY, ['fr'], 'fr', 'contact-c-fr'));
        // The router tries the original-slug redirect first, so `/fr/contact` reaches its own page.
        self::assertSame('contact-c-fr', $service->getRedirectPathIfUntranslated(self::V2_KEY, ['fr'], 'fr', 'contact'));
    }

    public function testNoFormerSlugRedirectForAWeglot1Key(): void
    {
        $service = new SlugService();
        \Craft::$app->getCache()->set($this->weglot1CacheKey($service, 'wg_v1key'), [], 0);

        self::assertNull($service->getRedirectPathIfFormerSlug('wg_v1key', ['fr'], 'fr', 'livre-dargent'));
    }

    public function testTranslatedSlugsTurnedOffServeNoSlugForEitherKeyVersion(): void
    {
        $service = new SlugService();
        $weglot1Maps = ['fr' => ['forward' => ['guestbook' => 'livre-v1'], 'reverse' => ['livre-v1' => 'guestbook']]];
        \Craft::$app->getCache()->set($this->weglot1CacheKey($service, 'wg_v1key'), $weglot1Maps, 0);
        Plugin::getInstance()->getTypedSettings()->enableTranslatedSlugs = false;

        self::assertSame([], $service->getSlugMapsFromCacheWithApiKey(self::V2_KEY, ['fr']));
        self::assertSame(0, $this->weglot2Calls);
        self::assertSame([], $service->getSlugMapsFromCacheWithApiKey('wg_v1key', ['fr']));
        self::assertNull($service->getRedirectPathIfUntranslated(self::V2_KEY, ['fr'], 'fr', 'guestbook'));
    }

    public function testTranslatedSlugsAreOnByDefault(): void
    {
        $service = new SlugService();

        self::assertTrue((new Settings())->enableTranslatedSlugs);
        self::assertSame('livre-de-bronze', $service->getRedirectPathIfUntranslated(self::V2_KEY, ['fr'], 'fr', 'guestbook'));
        self::assertSame(1, $this->weglot2Calls);
    }

    private function weglot1CacheKey(SlugService $service, string $apiKey): string
    {
        $method = new \ReflectionMethod($service, 'buildSlugsCacheKey');

        return (string) $method->invoke($service, $apiKey, ['fr']);
    }
}
