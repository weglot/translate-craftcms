<?php

declare(strict_types=1);

namespace weglot\craftweglot\tests\unit\controllers;

use PHPUnit\Framework\TestCase;
use weglot\craftweglot\controllers\RouterController;
use weglot\craftweglot\services\SlugService;

final class RouterControllerTest extends TestCase
{
    public function testRedirectTargetAtTheDomainRoot(): void
    {
        self::assertSame('/fr/livre-de-bronze', RouterController::buildLanguageRedirectTarget('', 'fr', 'livre-de-bronze', ''));
        self::assertSame('/fr/livre-de-bronze', RouterController::buildLanguageRedirectTarget('/', 'fr', 'livre-de-bronze', ''));
    }

    public function testRedirectTargetKeepsTheSubdirectoryPrefix(): void
    {
        self::assertSame(
            '/shop/fr/blog-fr/my-post',
            RouterController::buildLanguageRedirectTarget('/shop/', 'fr', 'blog-fr/my-post', '')
        );
    }

    public function testRedirectTargetKeepsTheQueryString(): void
    {
        self::assertSame(
            '/shop/fr/livre-de-bronze?page=2&sort=asc',
            RouterController::buildLanguageRedirectTarget('/shop', 'fr', 'livre-de-bronze', 'page=2&sort=asc')
        );
    }

    public function testRedirectTargetPercentEncodesSlugSegmentsOnly(): void
    {
        self::assertSame(
            '/shop/fr/caf%C3%A9/livre-d%27or?q=caf%C3%A9&page=2',
            RouterController::buildLanguageRedirectTarget('/shop', 'fr', "café/livre-d'or", 'q=caf%C3%A9&page=2')
        );
    }

    public function testRedirectTargetDoesNotEncodeAnEncodedSlugTwice(): void
    {
        self::assertSame('/fr/caf%C3%A9', RouterController::buildLanguageRedirectTarget('', 'fr', 'caf%c3%a9', ''));
    }

    public function testRedirectTargetLeavesAsciiPathsAndTrailingSlashUnchanged(): void
    {
        self::assertSame('/fr/blog-fr/my_post-2/', RouterController::buildLanguageRedirectTarget('', 'fr', 'blog-fr/my_post-2/', ''));
    }

    public function testOriginalSlugRedirectsToItsTranslationWithoutCacheCap(): void
    {
        self::assertSame(
            ['path' => 'livre-de-bronze', 'maxAge' => null],
            RouterController::resolveSlugRedirect($this->slugs(), 'key', ['fr'], 'fr', 'guestbook', false)
        );
    }

    public function testFormerSlugRedirectsToTheCurrentOneForADay(): void
    {
        self::assertSame(
            ['path' => 'livre-de-bronze', 'maxAge' => 86400],
            RouterController::resolveSlugRedirect($this->slugs(), 'key', ['fr'], 'fr', 'livre-dargent', false)
        );
    }

    /**
     * `contact` is the original slug of the contact page and a former slug of the guestbook: the
     * original-slug redirect must win.
     */
    public function testOriginalSlugRedirectComesBeforeTheFormerSlugOne(): void
    {
        self::assertSame(
            ['path' => 'contact-c-fr', 'maxAge' => null],
            RouterController::resolveSlugRedirect($this->slugs(), 'key', ['fr'], 'fr', 'contact', false)
        );
    }

    public function testCurrentTranslatedSlugIsServed(): void
    {
        self::assertNull(RouterController::resolveSlugRedirect($this->slugs(), 'key', ['fr'], 'fr', 'contact-c-fr', false));
    }

    public function testFormerSlugIsNotRedirectedForAjax(): void
    {
        self::assertNull(RouterController::resolveSlugRedirect($this->slugs(), 'key', ['fr'], 'fr', 'livre-dargent', true));
    }

    private function slugs(): SlugService
    {
        return new class extends SlugService {
            public function getSlugMapsFromCacheWithApiKey(string $apiKey, array $destinationLanguages): array
            {
                return [
                    'fr' => [
                        'forward' => ['guestbook' => 'livre-de-bronze', 'contact' => 'contact-c-fr'],
                        'reverse' => ['livre-de-bronze' => 'guestbook', 'contact-c-fr' => 'contact'],
                        'redirects' => ['livre-dargent' => 'livre-de-bronze', 'contact' => 'livre-de-bronze'],
                    ],
                ];
            }
        };
    }
}
