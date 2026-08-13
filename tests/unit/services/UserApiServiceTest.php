<?php

declare(strict_types=1);

namespace weglot\craftweglot\tests\unit\services;

use PHPUnit\Framework\TestCase;
use weglot\craftweglot\services\UserApiService;

final class UserApiServiceTest extends TestCase
{
    private const CACHE_KEY = 'weglot_workspace_slug';

    private UserApiService $userApiService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userApiService = new UserApiService();
        \Craft::$app->getCache()->delete(self::CACHE_KEY);
    }

    protected function tearDown(): void
    {
        \Craft::$app->getCache()->delete(self::CACHE_KEY);
        parent::tearDown();
    }

    public function testV1KeyHasNoWorkspaceSlug(): void
    {
        self::assertSame('', $this->userApiService->getWorkspaceSlug('wg_abc123'));
    }

    public function testEmptyKeyHasNoWorkspaceSlug(): void
    {
        self::assertSame('', $this->userApiService->getWorkspaceSlug(''));
    }

    public function testCachedSlugIsReturnedWithoutHittingTheApi(): void
    {
        \Craft::$app->getCache()->set(self::CACHE_KEY, 'my-workspace');

        self::assertSame('my-workspace', $this->userApiService->getWorkspaceSlug('sk_abc123'));
    }

    public function testCachedEmptySlugIsHonoured(): void
    {
        \Craft::$app->getCache()->set(self::CACHE_KEY, '');

        self::assertSame('', $this->userApiService->getWorkspaceSlug('sk_abc123'));
    }
}
