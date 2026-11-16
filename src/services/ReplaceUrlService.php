<?php

declare(strict_types=1);

namespace weglot\craftweglot\services;

use craft\base\Component;
use weglot\craftweglot\helpers\HelperReplaceUrl;
use weglot\craftweglot\Plugin;
use Weglot\Vendor\Weglot\Client\Api\LanguageEntry;
use yii\web\Response;

class ReplaceUrlService extends Component
{
    /** Enough to get past an XML prolog, stylesheet instructions and comments without reading a whole sitemap. */
    private const SITEMAP_SNIFF_BYTES = 2048;

    /**
     * Whether a body is an XML sitemap or sitemap index, judged from its first bytes only:
     * sitemaps can weigh several megabytes.
     */
    public function isSitemap(string $body): bool
    {
        $head = substr($body, 0, self::SITEMAP_SNIFF_BYTES);

        return 1 === preg_match('/^(?:\xEF\xBB\xBF)?\s*(?:(?:<\?.*?\?>|<!--.*?-->)\s*)*<(?:urlset|sitemapindex)[\s>]/s', $head);
    }

    /**
     * Rewrites a sitemap served on a translated URL so it lists the pages of that language.
     *
     * Sitemaps reach this point from a Response: SEO plugins (SEOmatic, ether/seo) return them from
     * a controller, so they never go through View::EVENT_AFTER_RENDER_PAGE_TEMPLATE and
     * TranslateService::processResponse(). Only the <loc> URLs are rewritten; nothing is sent to the
     * Weglot API.
     */
    public function rewriteSitemapResponse(Response $response): void
    {
        $content = $response->content;
        if (200 !== $response->getStatusCode() || null !== $response->stream || !\is_string($content) || '' === $content) {
            return;
        }

        // No Content-Type check: a Twig sitemap without an .xml extension is served as text/html,
        // and the sniff alone is strict enough.
        if ('' === trim(Plugin::getInstance()->getTypedSettings()->apiKey) || !$this->isSitemap($content)) {
            return;
        }

        try {
            $requestUrlService = Plugin::getInstance()->getRequestUrlService();
            $currentLanguage = $requestUrlService->getCurrentLanguage();
            $originalLanguage = Plugin::getInstance()->getLanguage()->getOriginalLanguage();
            if (
                !$currentLanguage instanceof LanguageEntry
                || !$originalLanguage instanceof LanguageEntry
                || $currentLanguage->getInternalCode() === $originalLanguage->getInternalCode()
                || !$requestUrlService->getWeglotUrl()->getForLanguage($currentLanguage, false)
            ) {
                return;
            }

            $response->content = $this->rewriteSitemap($content, $currentLanguage);
            // craft\web\Response::sendAndClose() sets it from the content before send() prepares the response.
            $response->getHeaders()->remove('Content-Length');
        } catch (\Throwable $e) {
            \Craft::warning('Sitemap not rewritten: '.$e->getMessage(), __METHOD__);
        }
    }

    /**
     * Points every <loc> of a sitemap or sitemap index at the given language. An entry whose URL
     * cannot be served in that language (excluded, another host, another language) is removed:
     * a sitemap may only list URLs under its own location. When every entry would be removed
     * (typically the site reached on another host than its URLs), the sitemap is left as it is.
     */
    public function rewriteSitemap(string $xml, LanguageEntry $language): string
    {
        $currentHost = strtolower((string) parse_url(Plugin::getInstance()->getRequestUrlService()->getFullUrl(), \PHP_URL_HOST));
        $entries = 0;
        $dropped = 0;

        // One pass over the document: a replace per URL across the whole sitemap is quadratic.
        $rewritten = preg_replace_callback(
            '#<(url|sitemap)\b[^>]*>.*?</\1>#s',
            function (array $entry) use ($language, $currentHost, &$entries, &$dropped): string {
                ++$entries;
                $rewrittenEntry = $this->rewriteSitemapEntry($entry[0], $language, $currentHost);
                if ('' === $rewrittenEntry) {
                    ++$dropped;
                }

                return $rewrittenEntry;
            },
            $xml
        );

        if (null === $rewritten) {
            \Craft::warning('Sitemap not rewritten: '.preg_last_error_msg(), __METHOD__);

            return $xml;
        }

        if ($entries > 0 && $dropped === $entries) {
            \Craft::warning(\sprintf('Sitemap not rewritten: none of its %d URLs can be served in "%s" on host "%s".', $entries, $language->getExternalCode(), $currentHost), __METHOD__);

            return $xml;
        }

        return $rewritten;
    }

    private function rewriteSitemapEntry(string $entry, LanguageEntry $language, string $currentHost): string
    {
        if (1 !== preg_match('#<loc>\s*([^<]*?)\s*</loc>#', $entry, $match, \PREG_OFFSET_CAPTURE)) {
            return $entry;
        }

        [$loc, $offset] = $match[1];
        $url = $this->sitemapUrlForLanguage(html_entity_decode($loc, \ENT_QUOTES | \ENT_XML1, 'UTF-8'), $language, $currentHost);
        if (null === $url) {
            return '';
        }

        return substr_replace($entry, htmlspecialchars($url, \ENT_QUOTES | \ENT_XML1, 'UTF-8'), $offset, \strlen($loc));
    }

    /**
     * The URL of a source page in the given language, built as its hreflang alternate is
     * (HrefLangService): custom URLs and translated slugs applied, trailing slash as in the source.
     */
    private function sitemapUrlForLanguage(string $url, LanguageEntry $language, string $currentHost): ?string
    {
        $scheme = strtolower((string) parse_url($url, \PHP_URL_SCHEME));
        $host = strtolower((string) parse_url($url, \PHP_URL_HOST));
        $requestUrlService = Plugin::getInstance()->getRequestUrlService();
        if (!\in_array($scheme, ['http', 'https'], true) || '' === $host || $host !== $currentHost) {
            return null;
        }

        $weglotUrl = $requestUrlService->createUrlObject($url);
        $urlLanguage = $weglotUrl->getCurrentLanguage()->getInternalCode();
        if ($urlLanguage === $language->getInternalCode()) {
            return $url;
        }
        if ($urlLanguage !== $weglotUrl->getDefault()->getInternalCode()) {
            return null;
        }

        $href = $weglotUrl->getForLanguage($language, false);
        if (!\is_string($href) || '' === $href) {
            return null;
        }

        $suffix = '';
        if (null !== $weglotUrl->getQuery()) {
            $suffix .= '?'.$weglotUrl->getQuery();
        }
        if (null !== $weglotUrl->getFragment()) {
            $suffix .= '#'.$weglotUrl->getFragment();
        }

        $prefix = $weglotUrl->getHost().'/'.$language->getExternalCode();
        if (!str_starts_with($href, $prefix.'/') || !str_ends_with($href, $suffix)) {
            return null;
        }

        // The Url object decodes the path: encode each segment again, once.
        $path = substr($href, \strlen($prefix), \strlen($href) - \strlen($prefix) - \strlen($suffix));
        $path = $requestUrlService->getPathForLanguage($path, $language);

        return $prefix.implode('/', array_map(rawurlencode(...), explode('/', $path))).$suffix;
    }

    /**
     * @param string $dom the DOM content as a string to be modified
     *
     * @return string the modified DOM string with replaced links and updated attributes
     */
    public function replaceLinkInDom(string $dom): string
    {
        $data = HelperReplaceUrl::getReplaceModifyLink();

        foreach ($data as $key => $value) {
            $dom = $this->modifyLink($value, $dom, $key);
        }

        $currentLanguage = Plugin::getInstance()->getRequestUrlService()->getCurrentLanguage();
        $currentUrl = Plugin::getInstance()->getRequestUrlService()->getWeglotUrl();

        if ($currentUrl->getForLanguage($currentLanguage, false)) {
            if ($currentLanguage->getExternalCode() !== $currentLanguage->getInternalCode()) {
                $dom = preg_replace(
                    '/<html (.*?)?lang=(\"|\')(\S*)(\"|\')/',
                    '<html $1lang=$2'.$currentLanguage->getExternalCode().'$4 weglot-lang=$2'.$currentLanguage->getInternalCode().'$4',
                    $dom
                );
            } else {
                $dom = preg_replace(
                    '/<html (.*?)?lang=(\"|\')(\S*)(\"|\')/',
                    '<html $1lang=$2'.$currentLanguage->getExternalCode().'$4',
                    $dom
                );
            }

            $dom = preg_replace(
                '/property="og:locale" content=(\"|\')(\S*)(\"|\')/',
                'property="og:locale" content=$1'.$currentLanguage->getExternalCode().'$3',
                (string) $dom
            );
        } else {
            $dom = preg_replace(
                '/<html (.*?)?lang=(\"|\')(\S*)(\"|\')/',
                '<html $1lang=$2$3$4 data-excluded-page="true"',
                $dom
            );
        }

        return $dom;
    }

    /**
     * @param string $pattern        the regular expression pattern to find links in the translated page
     * @param string $translatedPage the content of the page where links will be modified
     * @param string $type           The type of link modification to be performed (e.g., 'a', 'datalink', 'form', etc.).
     *
     * @return string the modified translated page with links processed based on the specified type
     */
    public function modifyLink(string $pattern, string $translatedPage, string $type): string
    {
        preg_match_all($pattern, $translatedPage, $out, \PREG_PATTERN_ORDER);

        if ([] === $out[0]) {
            return $translatedPage;
        }

        $countOut0 = \count($out[0]);
        $replaceLinkService = Plugin::getInstance()->getReplaceLinkService();

        for ($i = 0; $i < $countOut0; ++$i) {
            $sometags = $out[1][$i] ?? '';
            $quote1 = $out[2][$i] ?? '"';
            $currentUrl = $out[3][$i] ?? '';
            $quote2 = $quote1; // la quote fermante est égale à la quote ouvrante (backref \2 dans le regex)
            $sometags2 = $out[4][$i] ?? '';

            if (\strlen($currentUrl) >= 1500) { // Limit from WP plugin
                continue;
            }

            if (preg_match('#^/?(?:index\.php/)?actions/#i', $currentUrl)) {
                continue;
            }

            switch ($type) {
                case 'a':
                    $translatedPage = $replaceLinkService->replaceA($translatedPage, $currentUrl, $quote1, $quote2, $sometags, $sometags2);
                    break;
                case 'datalink':
                    $translatedPage = $replaceLinkService->replaceDatalink($translatedPage, $currentUrl, $quote1, $quote2, $sometags);
                    break;
                case 'dataurl':
                    $translatedPage = $replaceLinkService->replaceDataurl($translatedPage, $currentUrl, $quote1, $quote2, $sometags);
                    break;
                case 'datacart':
                    $translatedPage = $replaceLinkService->replaceDatacart($translatedPage, $currentUrl, $quote1, $quote2, $sometags);
                    break;
                case 'hxget':
                    $translatedPage = $replaceLinkService->replaceHxGet($translatedPage, $currentUrl, $quote1, $quote2, $sometags);
                    break;
                case 'hxpost':
                    $translatedPage = $replaceLinkService->replaceHxPost($translatedPage, $currentUrl, $quote1, $quote2, $sometags);
                    break;
                case 'hxput':
                    $translatedPage = $replaceLinkService->replaceHxPut($translatedPage, $currentUrl, $quote1, $quote2, $sometags);
                    break;
                case 'hxpatch':
                    $translatedPage = $replaceLinkService->replaceHxPatch($translatedPage, $currentUrl, $quote1, $quote2, $sometags);
                    break;
                case 'hxdelete':
                    $translatedPage = $replaceLinkService->replaceHxDelete($translatedPage, $currentUrl, $quote1, $quote2, $sometags);
                    break;
                case 'form':
                    $translatedPage = $replaceLinkService->replaceForm($translatedPage, $currentUrl, $quote1, $quote2, $sometags);
                    break;
                case 'canonical':
                    $translatedPage = $replaceLinkService->replaceCanonical($translatedPage, $currentUrl, $quote1, $quote2, $sometags);
                    break;
                case 'amp':
                    $translatedPage = $replaceLinkService->replaceAmp($translatedPage, $currentUrl, $quote1, $quote2, $sometags);
                    break;
                case 'meta':
                    $translatedPage = $replaceLinkService->replaceMeta($translatedPage, $currentUrl, $quote1, $quote2, $sometags);
                    break;
                case 'next':
                    $translatedPage = $replaceLinkService->replaceNext($translatedPage, $currentUrl, $quote1, $quote2, $sometags);
                    break;
                case 'prev':
                    $translatedPage = $replaceLinkService->replacePrev($translatedPage, $currentUrl, $quote1, $quote2, $sometags);
                    break;
            }
        }

        return $translatedPage;
    }
}
