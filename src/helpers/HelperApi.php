<?php

namespace weglot\craftweglot\helpers;

use craft\helpers\App;

class HelperApi
{
    private const ENV_PROD = 'production';
    private const ENV_STAGING = 'staging';

    private const API_URL_PROD = 'https://api.weglot.com';

    private const CDN_URL_PROD = 'https://cdn.weglot.com/';

    private const TPL_SWITCHERS_URL_PROD = self::CDN_URL_PROD . 'switchers/';


    public static function getEnvironment(): string
    {
        $env = App::env('WEGLOT_ENV');
        if ($env) {
            return $env === self::ENV_STAGING ? self::ENV_STAGING : self::ENV_PROD;
        }

        if (App::env('WEGLOT_DEV')) {
            return self::ENV_STAGING;
        }

        return self::ENV_PROD;
    }

	public static function getApiUrl(): string
	{
		return self::getEnvironment() === self::ENV_STAGING
			? App::env('WEGLOT_API_URL_STAGING')
			: self::API_URL_PROD;
	}

	public static function getCdnUrl(): string
	{
		return self::getEnvironment() === self::ENV_STAGING
			? App::env('WEGLOT_CDN_URL_STAGING')
			: self::CDN_URL_PROD;
	}

	public static function getTplSwitchersUrl(): string
    {
	    return self::getCdnUrl() . 'switchers/';
    }
}
