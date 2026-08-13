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

    public function testGetProductionRootCdnBaseIgnoresStagingOverrides(): void
    {
        putenv('WEGLOT_ENV=staging');
        putenv('WEGLOT_CDN_URL_STAGING=https://cdn.weglot.dev/');

        self::assertSame('https://cdn.weglot.dev', HelperApi::getRootCdnBase());
        self::assertSame('https://cdn.weglot.com', HelperApi::getProductionRootCdnBase());
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

    // -------------------------------------------------------------------------
    // env_us
    // -------------------------------------------------------------------------

    public function testUsEnvironmentIsRecognised(): void
    {
        putenv('WEGLOT_ENV=env_us');

        self::assertSame('env_us', HelperApi::getEnvironment());
        self::assertSame('https://api.weglot.us', HelperApi::getApiUrl());
        self::assertSame('https://cdn.weglot.us/', HelperApi::getCdnUrl());
    }

    public function testUsEnvironmentUsesV2CdnForV2Key(): void
    {
        putenv('WEGLOT_ENV=env_us');

        self::assertSame('https://cdn-v2.weglot.us/', HelperApi::getCdnUrlForKey('some-v2-key'));
    }

    public function testUnknownEnvironmentFallsBackToProduction(): void
    {
        putenv('WEGLOT_ENV=whatever');

        self::assertSame('production', HelperApi::getEnvironment());
    }

    // -------------------------------------------------------------------------
    // getDashboardUrl
    // -------------------------------------------------------------------------

    public function testGetDashboardUrlUsesAuthDomainForV2(): void
    {
        self::assertSame('https://dashboard.weglot.com', HelperApi::getDashboardUrl(HelperApi::VERSION_V1));
        self::assertSame('https://auth.weglot.com', HelperApi::getDashboardUrl(HelperApi::VERSION_V2));
    }

    public function testGetDashboardUrlUsesStagingHosts(): void
    {
        putenv('WEGLOT_ENV=staging');

        self::assertSame('https://dashboard.weglot.dev', HelperApi::getDashboardUrl(HelperApi::VERSION_V1));
        self::assertSame('https://auth.weglot.dev', HelperApi::getDashboardUrl(HelperApi::VERSION_V2));
    }

    public function testGetDashboardUrlFallsBackToProductionInUsEnvironment(): void
    {
        putenv('WEGLOT_ENV=env_us');

        self::assertSame('https://dashboard.weglot.com', HelperApi::getDashboardUrl(HelperApi::VERSION_V1));
        self::assertSame('https://auth.weglot.com', HelperApi::getDashboardUrl(HelperApi::VERSION_V2));
    }

    // -------------------------------------------------------------------------
    // getRegisterUrl
    // -------------------------------------------------------------------------

    public function testGetRegisterUrlV1HasNoSiteUrl(): void
    {
        self::assertSame(
            'https://dashboard.weglot.com/register-craft',
            HelperApi::getRegisterUrl(HelperApi::VERSION_V1, 'https://example.com/')
        );
    }

    public function testGetRegisterUrlV2CarriesEncodedSiteUrl(): void
    {
        self::assertSame(
            'https://auth.weglot.com/register/craft?url=https%3A%2F%2Fexample.com%2F',
            HelperApi::getRegisterUrl(HelperApi::VERSION_V2, 'https://example.com/')
        );
    }
}
