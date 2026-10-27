<?php

declare(strict_types=1);

namespace weglot\craftweglot\helpers;

class HelperQueryString
{
    private const TRACKING_PARAM_PREFIX = 'utm_';

    /**
     * Click ids and campaign markers added by ad, analytics and emailing platforms. Lowercase:
     * keys are compared case-insensitively.
     */
    private const TRACKING_PARAMS = [
        'gclid', 'gclsrc', 'dclid', 'gbraid', 'wbraid', 'gad_source', 'gad_campaignid', '_gl', 'srsltid',
        'fbclid', 'igshid', 'msclkid', 'ttclid', 'twclid', 'li_fat_id', 'epik', 'rdt_cid', 'sccid', 'yclid',
        'mc_cid', 'mc_eid', '_hsenc', '_hsmi', 'mkt_tok', '_kx', 's_kwcid', 'ef_id', 'irclickid',
    ];

    /**
     * Pairs are kept verbatim rather than rebuilt with parse_str() / http_build_query(), which
     * would rename dotted keys, rewrite `a[]=` and re-encode values.
     */
    public static function removeTrackingParams(string $query): string
    {
        $kept = [];
        foreach (explode('&', $query) as $pair) {
            if ('' === $pair || self::isTrackingParam(explode('=', $pair, 2)[0])) {
                continue;
            }
            $kept[] = $pair;
        }

        return implode('&', $kept);
    }

    public static function removeTrackingParamsFromUrl(string $url): string
    {
        $fragment = '';
        $hashPosition = strpos($url, '#');
        if (false !== $hashPosition) {
            $fragment = substr($url, $hashPosition);
            $url = substr($url, 0, $hashPosition);
        }

        $parts = explode('?', $url, 2);
        if (!isset($parts[1])) {
            return $url.$fragment;
        }

        $query = self::removeTrackingParams($parts[1]);

        return $parts[0].('' !== $query ? '?'.$query : '').$fragment;
    }

    private static function isTrackingParam(string $rawKey): bool
    {
        $key = strtolower(rawurldecode($rawKey));

        return str_starts_with($key, self::TRACKING_PARAM_PREFIX) || \in_array($key, self::TRACKING_PARAMS, true);
    }
}
