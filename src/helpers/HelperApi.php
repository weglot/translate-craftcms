<?php

namespace weglot\craftweglot\helpers;

use craft\helpers\App;

class HelperApi
{
    private const ENV_PROD = 'production';
    private const ENV_STAGING = 'staging';

    private const API_URL_PROD = 'https://api.weglot.com';
    private const CDN_URL_PROD = 'https://cdn.weglot.com/';

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

    public static function getTplSwitchersUrl(): string
    {
        return self::getCdnUrl().'switchers/';
    }
}
