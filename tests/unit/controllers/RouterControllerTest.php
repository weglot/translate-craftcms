<?php

declare(strict_types=1);

namespace weglot\craftweglot\tests\unit\controllers;

use PHPUnit\Framework\TestCase;
use weglot\craftweglot\controllers\RouterController;

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
}
