<?php

declare(strict_types=1);

namespace weglot\craftweglot\tests\unit\services;

use craft\base\Component;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use weglot\craftweglot\Plugin;
use weglot\craftweglot\services\LanguageService;
use weglot\craftweglot\services\ReplaceUrlService;
use weglot\craftweglot\services\RequestUrlService;
use weglot\craftweglot\services\SlugService;
use Weglot\Vendor\Weglot\Client\Api\LanguageEntry;
use Weglot\Vendor\Weglot\Util\Regex;
use Weglot\Vendor\Weglot\Util\Regex\RegexEnum;
use Weglot\Vendor\Weglot\Util\Url;
use yii\web\Response;

final class ReplaceUrlServiceSitemapTest extends TestCase
{
    private const REPLACED_COMPONENTS = ['language', 'requestUrlService', 'slug'];

    private const URLSET_OPEN = '<?xml version="1.0" encoding="UTF-8"?><?xml-stylesheet type="text/xsl" href="sitemap.xsl"?>'
        .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';

    private ReplaceUrlService $service;

    private LanguageEntry $en;

    private LanguageEntry $fr;

    private LanguageEntry $de;

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

        $this->en = new LanguageEntry('en', 'en', 'English', 'English', false);
        $this->fr = new LanguageEntry('fr', 'fr', 'French', 'Français', false);
        $this->de = new LanguageEntry('de', 'de', 'German', 'Deutsch', false);
        $this->injectSlugStub(['fr' => ['about' => 'a-propos']]);
        $this->injectRequest('https://example.com/fr/sitemap.xml');

        $this->service = new ReplaceUrlService();
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
    // isSitemap
    // -------------------------------------------------------------------------

    #[DataProvider('sitemapDetectionProvider')]
    public function testIsSitemap(string $body, bool $expected): void
    {
        self::assertSame($expected, $this->service->isSitemap($body));
    }

    /** @return array<string, array{string, bool}> */
    public static function sitemapDetectionProvider(): array
    {
        return [
            'urlset after prolog and stylesheet' => [self::URLSET_OPEN.'</urlset>', true],
            'sitemap index' => ['<?xml version="1.0"?><sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></sitemapindex>', true],
            'BOM, whitespace and comment' => ["\xEF\xBB\xBF\n  <?xml version=\"1.0\"?>\n<!-- generated -->\n<urlset>\n</urlset>", true],
            'no prolog' => ['<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>', true],
            'stylesheet' => ['<?xml version="1.0"?><xsl:stylesheet version="1.0"></xsl:stylesheet>', false],
            'rss feed' => ['<?xml version="1.0"?><rss version="2.0"><channel><link>https://example.com/</link></channel></rss>', false],
            'html' => ['<!DOCTYPE html><html><body><urlset></urlset></body></html>', false],
            'urlset deep in the body' => [str_repeat(' ', 3000).'<urlset></urlset>', false],
        ];
    }

    // -------------------------------------------------------------------------
    // rewriteSitemap
    // -------------------------------------------------------------------------

    #[DataProvider('locProvider')]
    public function testRewriteSitemapPointsLocAtTheLanguage(string $loc, string $expected): void
    {
        $xml = self::URLSET_OPEN.'<url><loc>'.$loc.'</loc><lastmod>2026-11-12</lastmod></url></urlset>';

        self::assertSame(
            self::URLSET_OPEN.'<url><loc>'.$expected.'</loc><lastmod>2026-11-12</lastmod></url></urlset>',
            $this->service->rewriteSitemap($xml, $this->fr)
        );
    }

    /** @return array<string, array{string, string}> */
    public static function locProvider(): array
    {
        return [
            // Translated slug applied; no trailing slash added (Craft has no slash redirect, hreflang has none).
            'translated slug, no trailing slash' => ['https://example.com/about', 'https://example.com/fr/a-propos'],
            'trailing slash kept' => ['https://example.com/about/team/', 'https://example.com/fr/a-propos/team/'],
            'home' => ['https://example.com/', 'https://example.com/fr/'],
            'host without path' => ['https://example.com', 'https://example.com/fr/'],
            'child sitemap' => ['https://example.com/sitemaps-25-section-pages-50-sitemap.xml', 'https://example.com/fr/sitemaps-25-section-pages-50-sitemap.xml'],
            'already in the language' => ['https://example.com/fr/a-propos', 'https://example.com/fr/a-propos'],
            // Escaped once in the XML, encoded once in the URL: neither decoded into the output.
            'encoded path and escaped query' => ['https://example.com/a%26b/c%20d?x=1&amp;y=2', 'https://example.com/fr/a%26b/c%20d?x=1&amp;y=2'],
            'markup in an encoded path' => ['https://example.com/%22%3E%3Cb%3E/x', 'https://example.com/fr/%22%3E%3Cb%3E/x'],
            'non-ASCII slug' => ['https://example.com/b%C3%A5t', 'https://example.com/fr/b%C3%A5t'],
            // Twig sitemaps often indent the URL; the whitespace is kept as it was.
            'whitespace around the URL' => ["\n    https://example.com/about\n  ", "\n    https://example.com/fr/a-propos\n  "],
        ];
    }

    public function testRewriteSitemapAppliesCustomUrls(): void
    {
        $this->injectRequest('https://example.com/fr/sitemap.xml', customUrls: ['fr' => ['contactez-nous' => 'contact']]);
        $xml = self::URLSET_OPEN.'<url><loc>https://example.com/contact</loc></url></urlset>';

        self::assertStringContainsString('<loc>https://example.com/fr/contactez-nous</loc>', $this->service->rewriteSitemap($xml, $this->fr));
    }

    public function testRewriteSitemapRewritesSitemapIndexEntries(): void
    {
        $xml = '<?xml version="1.0"?><sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
            .'<sitemap><loc>https://example.com/sitemaps-1-sitemap.xml</loc><lastmod>2026-11-12</lastmod></sitemap>'
            .'<sitemap><loc>https://example.com/sitemaps-2-sitemap.xml</loc></sitemap></sitemapindex>';

        self::assertSame(
            '<?xml version="1.0"?><sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
            .'<sitemap><loc>https://example.com/fr/sitemaps-1-sitemap.xml</loc><lastmod>2026-11-12</lastmod></sitemap>'
            .'<sitemap><loc>https://example.com/fr/sitemaps-2-sitemap.xml</loc></sitemap></sitemapindex>',
            $this->service->rewriteSitemap($xml, $this->fr)
        );
    }

    public function testRewriteSitemapLeavesImageAndVideoLocationsAlone(): void
    {
        $xml = self::URLSET_OPEN.'<url><loc>https://example.com/about</loc>'
            .'<image:image><image:loc>https://example.com/img/a.jpg</image:loc></image:image>'
            .'<video:video><video:content_loc>https://example.com/v.mp4</video:content_loc></video:video></url></urlset>';

        self::assertSame(
            self::URLSET_OPEN.'<url><loc>https://example.com/fr/a-propos</loc>'
            .'<image:image><image:loc>https://example.com/img/a.jpg</image:loc></image:image>'
            .'<video:video><video:content_loc>https://example.com/v.mp4</video:content_loc></video:video></url></urlset>',
            $this->service->rewriteSitemap($xml, $this->fr)
        );
    }

    #[DataProvider('droppedLocProvider')]
    public function testRewriteSitemapDropsEntriesNotServedInTheLanguage(string $loc): void
    {
        $this->injectRequest('https://example.com/fr/sitemap.xml', excludeUrls: [[new Regex(RegexEnum::START_WITH, '/private'), null]]);
        $xml = self::URLSET_OPEN.'<url><loc>'.$loc.'</loc></url><url><loc>https://example.com/about</loc></url></urlset>';

        self::assertSame(
            self::URLSET_OPEN.'<url><loc>https://example.com/fr/a-propos</loc></url></urlset>',
            $this->service->rewriteSitemap($xml, $this->fr)
        );
    }

    public function testRewriteSitemapKeepsTheSitemapWhenEveryEntryWouldBeDropped(): void
    {
        $xml = self::URLSET_OPEN.'<url><loc>https://www.example.com/about</loc></url><url><loc>https://www.example.com/contact</loc></url></urlset>';

        self::assertSame($xml, $this->service->rewriteSitemap($xml, $this->fr));
    }

    /** @return array<string, array{string}> */
    public static function droppedLocProvider(): array
    {
        return [
            'excluded URL' => ['https://example.com/private/page'],
            'another host' => ['https://other.example.org/about'],
            'another language' => ['https://example.com/de/about'],
            'relative URL' => ['/about'],
        ];
    }

    /**
     * Guards the single regex pass only, not the production cost per URL (createUrlObject() is
     * stubbed): at this size a replace per URL over the whole document takes several seconds.
     */
    public function testRewriteSitemapStaysLinearOnLargeSitemaps(): void
    {
        $entries = '';
        for ($i = 0; $i < 20000; ++$i) {
            $entries .= '<url><loc>https://example.com/page-'.$i.'</loc><lastmod>2026-11-12</lastmod></url>';
        }

        $start = hrtime(true);
        $rewritten = $this->service->rewriteSitemap(self::URLSET_OPEN.$entries.'</urlset>', $this->fr);
        $elapsedMs = (hrtime(true) - $start) / 1e6;

        self::assertSame(20000, substr_count($rewritten, '<loc>https://example.com/fr/page-'));
        self::assertLessThan(1000, $elapsedMs);
    }

    // -------------------------------------------------------------------------
    // rewriteSitemapResponse
    // -------------------------------------------------------------------------

    public function testRewriteSitemapResponseRewritesAnXmlSitemap(): void
    {
        $response = $this->makeResponse(self::URLSET_OPEN.'<url><loc>https://example.com/about</loc></url></urlset>', 'text/xml; charset=utf-8');
        $response->getHeaders()->set('Content-Length', '42');

        $this->service->rewriteSitemapResponse($response);

        self::assertSame(self::URLSET_OPEN.'<url><loc>https://example.com/fr/a-propos</loc></url></urlset>', $response->content);
        self::assertFalse($response->getHeaders()->has('Content-Length'));
    }

    public function testRewriteSitemapResponseAcceptsAMissingContentType(): void
    {
        $response = $this->makeResponse(self::URLSET_OPEN.'<url><loc>https://example.com/about</loc></url></urlset>', null);

        $this->service->rewriteSitemapResponse($response);

        self::assertStringContainsString('https://example.com/fr/a-propos', (string) $response->content);
    }

    public function testRewriteSitemapResponseRewritesASitemapServedAsHtml(): void
    {
        // A Twig sitemap template without an .xml extension is served as text/html.
        $response = $this->makeResponse(self::URLSET_OPEN.'<url><loc>https://example.com/about</loc></url></urlset>', 'text/html; charset=UTF-8');

        $this->service->rewriteSitemapResponse($response);

        self::assertStringContainsString('https://example.com/fr/a-propos', (string) $response->content);
    }

    public function testRewriteSitemapResponseIgnoresNonSitemapBodies(): void
    {
        $body = '<!DOCTYPE html><html><body><a href="https://example.com/about">About</a></body></html>';
        $response = $this->makeResponse($body, 'text/html; charset=UTF-8');

        $this->service->rewriteSitemapResponse($response);

        self::assertSame($body, $response->content);
    }

    public function testRewriteSitemapResponseIgnoresStreamedResponses(): void
    {
        $body = self::URLSET_OPEN.'<url><loc>https://example.com/about</loc></url></urlset>';
        $response = $this->makeResponse($body, 'application/xml');
        $response->stream = [fopen('php://memory', 'r'), 0, 0];

        $this->service->rewriteSitemapResponse($response);

        self::assertSame($body, $response->content);
    }

    public function testRewriteSitemapResponseIsSkippedWithoutAnApiKey(): void
    {
        Plugin::getInstance()->getTypedSettings()->apiKey = '';
        $body = self::URLSET_OPEN.'<url><loc>https://example.com/about</loc></url></urlset>';
        $response = $this->makeResponse($body, 'application/xml');

        $this->service->rewriteSitemapResponse($response);

        self::assertSame($body, $response->content);
    }

    public function testRewriteSitemapResponseServesTheSitemapUnchangedWhenTheRewriteFails(): void
    {
        Plugin::getInstance()->set('language', new class extends LanguageService {
            public function getOriginalLanguage(): ?LanguageEntry
            {
                throw new \RuntimeException('Weglot API unreachable');
            }
        });
        $body = self::URLSET_OPEN.'<url><loc>https://example.com/about</loc></url></urlset>';
        $response = $this->makeResponse($body, 'application/xml');
        $response->getHeaders()->set('Content-Length', '42');

        $this->service->rewriteSitemapResponse($response);

        self::assertSame($body, $response->content);
        self::assertSame('42', $response->getHeaders()->get('Content-Length'));
    }

    public function testRewriteSitemapResponseIgnoresErrorResponses(): void
    {
        $body = self::URLSET_OPEN.'<url><loc>https://example.com/about</loc></url></urlset>';
        $response = $this->makeResponse($body, 'application/xml');
        $response->setStatusCode(404);

        $this->service->rewriteSitemapResponse($response);

        self::assertSame($body, $response->content);
    }

    public function testRewriteSitemapResponseLeavesTheSourceLanguageSitemapAlone(): void
    {
        $this->injectRequest('https://example.com/sitemap.xml');
        $body = self::URLSET_OPEN.'<url><loc>https://example.com/about</loc></url></urlset>';
        $response = $this->makeResponse($body, 'application/xml');

        $this->service->rewriteSitemapResponse($response);

        self::assertSame($body, $response->content);
    }

    public function testRewriteSitemapResponseLeavesAnExcludedSitemapAlone(): void
    {
        $this->injectRequest('https://example.com/fr/sitemap.xml', excludeUrls: [[new Regex(RegexEnum::IS_EXACTLY, '/sitemap.xml'), null]]);
        $body = self::URLSET_OPEN.'<url><loc>https://example.com/about</loc></url></urlset>';
        $response = $this->makeResponse($body, 'application/xml');

        $this->service->rewriteSitemapResponse($response);

        self::assertSame($body, $response->content);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeResponse(string $content, ?string $contentType): Response
    {
        $response = new Response();
        $response->content = $content;
        if (null !== $contentType) {
            $response->getHeaders()->set('Content-Type', $contentType);
        }

        return $response;
    }

    /**
     * Real Url objects for the request and for every <loc>: original 'en', destinations 'fr' and 'de'.
     *
     * @param array<string, array<string, string>> $customUrls  internal code => [translated => original]
     * @param array<int, mixed>                    $excludeUrls
     */
    private function injectRequest(string $requestUrl, array $customUrls = [], array $excludeUrls = []): void
    {
        $en = $this->en;
        $destinations = [$this->fr, $this->de];
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
}
