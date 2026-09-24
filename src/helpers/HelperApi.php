<?php

declare(strict_types=1);

namespace weglot\craftweglot\helpers;

use craft\helpers\App;

class HelperApi
{
    public const VERSION_V1 = 1;
    public const VERSION_V2 = 2;

    private const ENV_PROD = 'production';
    private const ENV_STAGING = 'staging';
    private const ENV_US = 'env_us';

    private const API_URL_PROD = 'https://api.weglot.com';
    private const API_URL_US = 'https://api.weglot.us';
    private const API_URL_V2_PROD = 'https://api.eu.weglot.com';
    private const API_URL_V2_STAGING = 'https://api.eu.weglot.dev';
    private const CDN_URL_PROD = 'https://cdn.weglot.com/';
    private const CDN_URL_US = 'https://cdn.weglot.us/';
    private const CDN_URL_V2_PROD = 'https://cdn-v2.weglot.com/';
    private const CDN_URL_V2_STAGING = 'https://cdn-v2.weglot.dev/';
    private const CDN_URL_V2_US = 'https://cdn-v2.weglot.us/';

    private const DASHBOARD_URL_PROD = 'https://dashboard.weglot.com';
    private const DASHBOARD_URL_STAGING = 'https://dashboard.weglot.dev';
    private const AUTH_URL_PROD = 'https://auth.weglot.com';
    private const AUTH_URL_STAGING = 'https://auth.weglot.dev';

    private const REGISTER_PATH_V1 = '/register-craft';
    private const REGISTER_PATH_V2 = '/register/craft';

    public static function getEnvironment(): string
    {
        $env = App::env('WEGLOT_ENV');
        if (\is_string($env) && '' !== $env) {
            return match ($env) {
                self::ENV_STAGING => self::ENV_STAGING,
                self::ENV_US => self::ENV_US,
                default => self::ENV_PROD,
            };
        }

        $devEnv = App::env('WEGLOT_DEV');
        if (\is_string($devEnv) && '' !== $devEnv) {
            return self::ENV_STAGING;
        }

        return self::ENV_PROD;
    }

    public static function getApiUrl(): string
    {
        $env = self::getEnvironment();
        if (self::ENV_US === $env) {
            return self::API_URL_US;
        }

        $envUrl = App::env('WEGLOT_API_URL_STAGING');

        return self::ENV_STAGING === $env && \is_string($envUrl)
            ? $envUrl
            : self::API_URL_PROD;
    }

    public static function getCdnUrl(): string
    {
        $env = self::getEnvironment();
        if (self::ENV_US === $env) {
            return self::CDN_URL_US;
        }

        $cdnUrl = App::env('WEGLOT_CDN_URL_STAGING');

        return self::ENV_STAGING === $env && \is_string($cdnUrl)
            ? $cdnUrl
            : self::CDN_URL_PROD;
    }

    public static function getRootCdnBase(): string
    {
        return rtrim(self::getCdnUrl(), '/');
    }

    /**
     * Production CDN base, whatever the current environment. Only the production
     * CDN hosts the remote routing configuration, so staging installs must read it
     * from there too — same as the WordPress plugin.
     */
    public static function getProductionRootCdnBase(): string
    {
        return rtrim(self::CDN_URL_PROD, '/');
    }

    public static function getWeglotJsUrl(): string
    {
        return self::getRootCdnBase().'/weglot.min.js';
    }

    public static function getTplSwitchersUrl(): string
    {
        return self::getCdnUrl().'switchers/';
    }

    public static function isV2ApiKey(string $apiKey): bool
    {
        return '' !== $apiKey && !str_starts_with($apiKey, 'wg_');
    }

    /**
     * Fallback V2 API host, used only when the project-settings response does
     * not provide an `api_base_url` (which carries the project's region).
     */
    public static function getApiUrlV2(): string
    {
        return self::ENV_STAGING === self::getEnvironment()
            ? self::API_URL_V2_STAGING
            : self::API_URL_V2_PROD;
    }

    public static function getCdnUrlForKey(string $apiKey): string
    {
        if (!self::isV2ApiKey($apiKey)) {
            return self::getCdnUrl();
        }

        return match (self::getEnvironment()) {
            self::ENV_STAGING => self::CDN_URL_V2_STAGING,
            self::ENV_US => self::CDN_URL_V2_US,
            default => self::CDN_URL_V2_PROD,
        };
    }

    /**
     * Dashboard host for the given onboarding version. V2 onboarding lives on the
     * auth domain. The US environment has no dedicated dashboard and falls back to
     * production, as in the WordPress plugin.
     */
    public static function getDashboardUrl(int $version): string
    {
        $isStaging = self::ENV_STAGING === self::getEnvironment();

        if (self::VERSION_V2 === $version) {
            return $isStaging ? self::AUTH_URL_STAGING : self::AUTH_URL_PROD;
        }

        return $isStaging ? self::DASHBOARD_URL_STAGING : self::DASHBOARD_URL_PROD;
    }

    public static function getRegisterUrl(int $version, string $siteUrl): string
    {
        if (self::VERSION_V2 === $version) {
            return self::getDashboardUrl($version).self::REGISTER_PATH_V2.'?url='.urlencode($siteUrl);
        }

        return self::getDashboardUrl($version).self::REGISTER_PATH_V1;
    }
}
