<?php

declare(strict_types=1);

namespace weglot\craftweglot\tests\unit\services;

use PHPUnit\Framework\TestCase;
use weglot\craftweglot\services\UserApiService;

final class UserApiServiceTest extends TestCase
{
    private const V2_KEY = 'sk_abc123';

    private UserApiService $userApiService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userApiService = new UserApiService();
        \Craft::$app->getCache()->flush();
    }

    protected function tearDown(): void
    {
        \Craft::$app->getCache()->flush();
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
        \Craft::$app->getCache()->set(UserApiService::workspaceCacheKey(self::V2_KEY), 'my-workspace');

        self::assertSame('my-workspace', $this->userApiService->getWorkspaceSlug(self::V2_KEY));
    }

    public function testCachedEmptySlugIsHonoured(): void
    {
        \Craft::$app->getCache()->set(UserApiService::workspaceCacheKey(self::V2_KEY), '');

        self::assertSame('', $this->userApiService->getWorkspaceSlug(self::V2_KEY));
    }

    /**
     * A plain settings save never clears this cache, so a slug cached for one key
     * must not be served after the user switches to another project.
     */
    public function testCacheIsScopedToTheApiKey(): void
    {
        self::assertNotSame(
            UserApiService::workspaceCacheKey(self::V2_KEY),
            UserApiService::workspaceCacheKey('sk_other')
        );

        \Craft::$app->getCache()->set(UserApiService::workspaceCacheKey(self::V2_KEY), 'workspace-a');

        // No entry for the new key: nothing stale is served, and with no HTTP call
        // reachable from the test bootstrap the fetch yields an empty slug.
        self::assertSame('', $this->userApiService->getWorkspaceSlug('sk_other'));
    }
}
