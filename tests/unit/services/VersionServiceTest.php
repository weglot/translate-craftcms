<?php

declare(strict_types=1);

namespace weglot\craftweglot\tests\unit\services;

use PHPUnit\Framework\TestCase;
use weglot\craftweglot\helpers\HelperApi;
use weglot\craftweglot\services\VersionService;

final class VersionServiceTest extends TestCase
{
    private const CACHE_KEY = 'weglot_v2_percentage_split';

    private VersionService $versionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->versionService = new VersionService();
    }

    protected function tearDown(): void
    {
        \Craft::$app->getCache()->delete(self::CACHE_KEY);
        parent::tearDown();
    }

    public function testCachedPercentageIsReturnedWithoutHittingTheCdn(): void
    {
        \Craft::$app->getCache()->set(self::CACHE_KEY, 42);

        self::assertSame(42, $this->versionService->getV2PercentageSplit());
    }

    public function testZeroPercentAlwaysSelectsV1(): void
    {
        \Craft::$app->getCache()->set(self::CACHE_KEY, 0);

        for ($i = 0; $i < 20; ++$i) {
            self::assertSame(HelperApi::VERSION_V1, $this->versionService->getRandomOnboardingVersion());
        }
    }

    public function testHundredPercentAlwaysSelectsV2(): void
    {
        \Craft::$app->getCache()->set(self::CACHE_KEY, 100);

        for ($i = 0; $i < 20; ++$i) {
            self::assertSame(HelperApi::VERSION_V2, $this->versionService->getRandomOnboardingVersion());
        }
    }

    public function testNegativePercentageIsTreatedAsDisabled(): void
    {
        \Craft::$app->getCache()->set(self::CACHE_KEY, -10);

        self::assertSame(HelperApi::VERSION_V1, $this->versionService->getRandomOnboardingVersion());
    }
}
