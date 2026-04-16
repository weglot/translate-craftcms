<?php

declare(strict_types=1);

namespace weglot\craftweglot\tests\unit\services;

use PHPUnit\Framework\TestCase;
use weglot\craftweglot\services\SlugService;

final class SlugServiceTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Fixtures & factory
    // -------------------------------------------------------------------------

    /**
     * Default slug maps used across most tests:
     *   fr → blog ↔ blog-fr, about ↔ a-propos
     *   de → blog ↔ blog-de
     *
     * @return array<string, array{forward: array<string,string>, reverse: array<string,string>}>
     */
    private static function defaultMaps(): array
    {
        return [
            'fr' => [
                'forward' => ['blog' => 'blog-fr', 'about' => 'a-propos'],
                'reverse' => ['blog-fr' => 'blog', 'a-propos' => 'about'],
            ],
            'de' => [
                'forward' => ['blog' => 'blog-de'],
                'reverse' => ['blog-de' => 'blog'],
            ],
        ];
    }

    /**
     * Build a SlugService stub whose getSlugMapsFromCacheWithApiKey() returns
     * $maps directly, bypassing all API and Craft cache calls.
     *
     * @param array<string, array{forward: array<string,string>, reverse: array<string,string>}> $maps
     */
    private function makeSvc(array $maps = []): SlugService
    {
        return new class($maps) extends SlugService {
            /** @param array<string, array{forward: array<string,string>, reverse: array<string,string>}> $maps */
            public function __construct(private readonly array $maps)
            {
                parent::__construct();
            }

            /** @return array<string, array{forward: array<string,string>, reverse: array<string,string>}> */
            public function getSlugMapsFromCacheWithApiKey(string $apiKey, array $destinationLanguages): array
            {
                return $this->maps;
            }
        };
    }

    // -------------------------------------------------------------------------
    // translateSlug
    // -------------------------------------------------------------------------

    public function testTranslateSlugReturnsTranslatedSlugWhenFoundInForwardMap(): void
    {
        $svc = $this->makeSvc(self::defaultMaps());
        self::assertSame('blog-fr', $svc->translateSlug('key', ['fr'], 'fr', 'blog'));
    }

    public function testTranslateSlugReturnsNullWhenSlugNotInForwardMap(): void
    {
        $svc = $this->makeSvc(self::defaultMaps());
        self::assertNull($svc->translateSlug('key', ['fr'], 'fr', 'contact'));
    }

    public function testTranslateSlugReturnsNullWhenLanguageHasNoEntry(): void
    {
        $svc = $this->makeSvc(self::defaultMaps());
        // 'es' is not present in the maps at all
        self::assertNull($svc->translateSlug('key', ['es'], 'es', 'blog'));
    }

    public function testTranslateSlugReturnsNullForEmptySlug(): void
    {
        $svc = $this->makeSvc(self::defaultMaps());
        self::assertNull($svc->translateSlug('key', ['fr'], 'fr', ''));
    }

    public function testTranslateSlugReturnsNullForEmptyLanguage(): void
    {
        $svc = $this->makeSvc(self::defaultMaps());
        self::assertNull($svc->translateSlug('key', ['fr'], '', 'blog'));
    }

    // -------------------------------------------------------------------------
    // getInternalPathIfTranslatedSlug
    // -------------------------------------------------------------------------

    public function testGetInternalPathReturnsOriginalSlugForTranslatedFirstSegment(): void
    {
        $svc = $this->makeSvc(self::defaultMaps());
        // 'blog-fr' is the translated slug → internal path is 'blog'
        self::assertSame('blog', $svc->getInternalPathIfTranslatedSlug('key', ['fr'], 'fr', 'blog-fr'));
    }

    public function testGetInternalPathReturnsNullWhenFirstSegmentNotInReverseMap(): void
    {
        $svc = $this->makeSvc(self::defaultMaps());
        // 'news' has no reverse mapping in 'fr'
        self::assertNull($svc->getInternalPathIfTranslatedSlug('key', ['fr'], 'fr', 'news'));
    }

    public function testGetInternalPathReplacesOnlyFirstSegmentOfMultiSegmentPath(): void
    {
        $svc = $this->makeSvc(self::defaultMaps());
        // 'blog-fr/2024/my-post' → 'blog/2024/my-post'
        self::assertSame('blog/2024/my-post', $svc->getInternalPathIfTranslatedSlug('key', ['fr'], 'fr', 'blog-fr/2024/my-post'));
    }

    public function testGetInternalPathReturnsNullForEmptyPath(): void
    {
        $svc = $this->makeSvc(self::defaultMaps());
        self::assertNull($svc->getInternalPathIfTranslatedSlug('key', ['fr'], 'fr', ''));
    }

    // -------------------------------------------------------------------------
    // getRedirectPathIfUntranslated
    // -------------------------------------------------------------------------

    public function testGetRedirectPathReturnsPathWithTranslatedFirstSegment(): void
    {
        $svc = $this->makeSvc(self::defaultMaps());
        // 'blog' → 'blog-fr' for 'fr'
        self::assertSame('blog-fr', $svc->getRedirectPathIfUntranslated('key', ['fr'], 'fr', 'blog'));
    }

    public function testGetRedirectPathPreservesTrailingSegmentsAfterTranslation(): void
    {
        $svc = $this->makeSvc(self::defaultMaps());
        // 'blog/my-post' → 'blog-fr/my-post'
        self::assertSame('blog-fr/my-post', $svc->getRedirectPathIfUntranslated('key', ['fr'], 'fr', 'blog/my-post'));
    }

    public function testGetRedirectPathReturnsNullWhenFirstSegmentHasNoTranslation(): void
    {
        $svc = $this->makeSvc(self::defaultMaps());
        self::assertNull($svc->getRedirectPathIfUntranslated('key', ['fr'], 'fr', 'contact'));
    }

    public function testGetRedirectPathReturnsNullForEmptyPath(): void
    {
        $svc = $this->makeSvc(self::defaultMaps());
        self::assertNull($svc->getRedirectPathIfUntranslated('key', ['fr'], 'fr', ''));
    }

    // -------------------------------------------------------------------------
    // translateUrlForLanguage
    // -------------------------------------------------------------------------

    public function testTranslateUrlForLanguageRewritesFirstSegmentInLanguagePrefixedUrl(): void
    {
        $svc = $this->makeSvc(self::defaultMaps());
        $url = 'https://example.com/fr/blog/my-post';
        // 'blog' → 'blog-fr', rest preserved
        self::assertSame('https://example.com/fr/blog-fr/my-post', $svc->translateUrlForLanguage('key', ['fr'], 'fr', $url));
    }

    public function testTranslateUrlForLanguageReturnsOriginalWhenPathPrefixDoesNotMatch(): void
    {
        $svc = $this->makeSvc(self::defaultMaps());
        // URL starts with '/de/' but we request 'fr' → no match
        $url = 'https://example.com/de/blog/my-post';
        self::assertSame($url, $svc->translateUrlForLanguage('key', ['fr'], 'fr', $url));
    }

    public function testTranslateUrlForLanguageReturnsOriginalWhenNoTranslationForFirstSegment(): void
    {
        $svc = $this->makeSvc(self::defaultMaps());
        // 'contact' has no translation in 'fr' forward map
        $url = 'https://example.com/fr/contact';
        self::assertSame($url, $svc->translateUrlForLanguage('key', ['fr'], 'fr', $url));
    }

    public function testTranslateUrlForLanguagePreservesQueryStringAndFragment(): void
    {
        $svc = $this->makeSvc(self::defaultMaps());
        $url = 'https://example.com/fr/blog?page=2#comments';
        self::assertSame('https://example.com/fr/blog-fr?page=2#comments', $svc->translateUrlForLanguage('key', ['fr'], 'fr', $url));
    }

    // -------------------------------------------------------------------------
    // getSlugMapsFromApiWithApiKey — pure logic (no HTTP)
    // -------------------------------------------------------------------------

    public function testGetSlugMapsFromApiReturnsEmptyArrayForNoDestinationLanguages(): void
    {
        // With an empty languages list the method returns immediately before
        // any cache or HTTP access.
        $svc = new SlugService();
        self::assertSame([], $svc->getSlugMapsFromApiWithApiKey('key', []));
    }
}
