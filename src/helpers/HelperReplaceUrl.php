<?php

namespace weglot\craftweglot\helpers;

class HelperReplaceUrl
{
    /**
     * @return array<string, string>
     */
    public static function getReplaceModifyLink(): array
    {
        return [
            'a' => '/<a(?![^>]*wg-excluded-link)([^>]+?)?href=(\"|\')(?!\/(?:index\.php\/)?actions\/)([^\s>]+?)\2([^>]*)?>/',
            'datalink' => '/<([^>]+?)?(?!wg-excluded-link)data-link=(\"|\')(?!\/(?:index\.php\/)?actions\/)([^\s>]+?)\2([^>]+?)?>/',
            'dataurl' => '/<([^>]+?)?(?!wg-excluded-link)data-url=(\"|\')(?!\/(?:index\.php\/)?actions\/)([^\s>]+?)\2([^>]+?)?>/',
            'datacart' => '/<([^>]+?)?(?!wg-excluded-link)data-cart-url=(\"|\')(?!\/(?:index\.php\/)?actions\/)([^\s>]+?)\2([^>]+?)?>/',
            'form' => '/<form(?![^>]*wg-excluded-link)([^>]+?)?action=(\"|\')(?!\/(?:index\.php\/)?actions\/)([^\s>]+?)\2/',
            'canonical' => '/<link(?![^>]*wg-excluded-link) rel="canonical"([^>]*)?href=(\"|\')(?!\/(?:index\.php\/)?actions\/)([^\s>]+?)\2/',
            'amp' => '/<link(?![^>]*wg-excluded-link) rel="amphtml"([^>]*)?href=(\"|\')(?!\/(?:index\.php\/)?actions\/)([^\s>]+?)\2/',
            'meta' => '/<meta(?![^>]*wg-excluded-link) property="og:url"([^>]*)?content=(\"|\')(?!\/(?:index\.php\/)?actions\/)([^\s>]+?)\2/',
            'next' => '/<link(?![^>]*wg-excluded-link) rel="next"([^>]*)?href=(\"|\')(?!\/(?:index\.php\/)?actions\/)([^\s>]+?)\2/',
            'prev' => '/<link(?![^>]*wg-excluded-link) rel="prev"([^>]*)?href=(\"|\')(?!\/(?:index\.php\/)?actions\/)([^\s>]+?)\2/',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function getReplaceModifyLinkInXml(): array
    {
        return [
            'loc' => '/<loc>(.*?)<\/loc>/',
        ];
    }
}
