<?php

declare(strict_types=1);

namespace weglot\craftweglot\helpers;

use craft\helpers\App;

class HelperApi
{
    private const ENV_PROD = 'production';
    private const ENV_STAGING = 'staging';

    private const API_URL_PROD = 'https://api.weglot.com';
    private const API_URL_V2_PROD = 'https://api.eu.weglot.com';
    private const API_URL_V2_STAGING = 'https://api.eu.weglot.dev';
    private const CDN_URL_PROD = 'https://cdn.weglot.com/';
    private const CDN_URL_V2_PROD = 'https://cdn-v2.weglot.com/';
    private const CDN_URL_V2_STAGING = 'https://cdn-v2.weglot.dev/';

    public static function getEnvironment(): string
    {
        $env = App::env('WEGLOT_ENV');
        if (\is_string($env) && '' !== $env) {
            return self::ENV_STAGING === $env ? self::ENV_STAGING : self::ENV_PROD;
        }

        $devEnv = App::env('WEGLOT_DEV');
        if (\is_string($devEnv) && '' !== $devEnv) {
            return self::ENV_STAGING;
        }

        return self::ENV_PROD;
    }

    public static function getApiUrl(): string
    {
        $envUrl = App::env('WEGLOT_API_URL_STAGING');

        return self::ENV_STAGING === self::getEnvironment() && \is_string($envUrl)
            ? $envUrl
            : self::API_URL_PROD;
    }

    public static function getCdnUrl(): string
    {
        $cdnUrl = App::env('WEGLOT_CDN_URL_STAGING');

        return self::ENV_STAGING === self::getEnvironment() && \is_string($cdnUrl)
            ? $cdnUrl
            : self::CDN_URL_PROD;
    }

    public static function getRootCdnBase(): string
    {
        return rtrim(self::getCdnUrl(), '/');
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

        return self::ENV_STAGING === self::getEnvironment()
            ? self::CDN_URL_V2_STAGING
            : self::CDN_URL_V2_PROD;
    }
}
