<?php

declare(strict_types=1);

namespace weglot\craftweglot\tests\unit\helpers;

use PHPUnit\Framework\TestCase;
use weglot\craftweglot\helpers\HelperApi;

final class HelperApiTest extends TestCase
{
    /** @var array<string,string|false> */
    private array $savedEnv = [];

    protected function setUp(): void
    {
        parent::setUp();
        // Snapshot and clear any staging env overrides so every test starts
        // from a clean production environment.
        foreach (['WEGLOT_ENV', 'WEGLOT_DEV', 'WEGLOT_API_URL_STAGING', 'WEGLOT_CDN_URL_STAGING'] as $key) {
            $this->savedEnv[$key] = getenv($key);
            putenv($key);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->savedEnv as $key => $value) {
            if (false !== $value) {
                putenv("$key=$value");
            } else {
                putenv($key);
            }
        }
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // getApiUrl
    // -------------------------------------------------------------------------

    public function testGetApiUrlReturnsProductionUrlByDefault(): void
    {
        self::assertSame('https://api.weglot.com', HelperApi::getApiUrl());
    }

    // -------------------------------------------------------------------------
    // getCdnUrl
    // -------------------------------------------------------------------------

    public function testGetCdnUrlReturnsProductionUrlByDefault(): void
    {
        self::assertSame('https://cdn.weglot.com/', HelperApi::getCdnUrl());
    }

    // -------------------------------------------------------------------------
    // getRootCdnBase
    // -------------------------------------------------------------------------

    public function testGetRootCdnBaseStripsTrailingSlash(): void
    {
        // getCdnUrl() ends with '/' — getRootCdnBase() must produce the same URL without it.
        self::assertSame(rtrim(HelperApi::getCdnUrl(), '/'), HelperApi::getRootCdnBase());
    }

    // -------------------------------------------------------------------------
    // getWeglotJsUrl
    // -------------------------------------------------------------------------

    public function testGetWeglotJsUrlEndsWithWeglotMinJs(): void
    {
        self::assertStringEndsWith('/weglot.min.js', HelperApi::getWeglotJsUrl());
    }

    // -------------------------------------------------------------------------
    // getTplSwitchersUrl
    // -------------------------------------------------------------------------

    public function testGetTplSwitchersUrlEndsWithSwitchersSlash(): void
    {
        self::assertStringEndsWith('switchers/', HelperApi::getTplSwitchersUrl());
    }
}
