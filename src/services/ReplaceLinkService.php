<?php

namespace weglot\craftweglot\services;

use craft\base\Component;
use Weglot\Client\Api\LanguageEntry;
use weglot\craftweglot\Plugin;

class ReplaceLinkService extends Component
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private readonly RequestUrlService $requestUrlService,
        array $config = [],
    ) {
        parent::__construct($config);
    }

    public function replaceUrl(string $url, LanguageEntry $language, bool $evenExcluded = true): string
    {
        $weglotUrl = $this->requestUrlService->createUrlObject($url);
        $replacedUrl = $weglotUrl->getForLanguage($language, $evenExcluded);

        if (!$replacedUrl) {
            return $url;
        }

        $parsedUrl = parse_url($replacedUrl);

        if (isset($parsedUrl['path']) && ('' !== $parsedUrl['path'] && '0' !== $parsedUrl['path']) && !str_ends_with($parsedUrl['path'], '/')) {
            $parsedUrl['path'] .= '/';
        }

        $rebuiltUrl = '';
        if (isset($parsedUrl['scheme'], $parsedUrl['host'])) {
            $rebuiltUrl .= $parsedUrl['scheme'].'://'.$parsedUrl['host'];
        }
        if (isset($parsedUrl['path']) && ('' !== $parsedUrl['path'] && '0' !== $parsedUrl['path'])) {
            $rebuiltUrl .= $parsedUrl['path'];
        }
        if (isset($parsedUrl['query']) && ('' !== $parsedUrl['query'] && '0' !== $parsedUrl['query'])) {
            $rebuiltUrl .= '?'.$parsedUrl['query'];
        }
        if (isset($parsedUrl['fragment']) && ('' !== $parsedUrl['fragment'] && '0' !== $parsedUrl['fragment'])) {
            $rebuiltUrl .= '#'.$parsedUrl['fragment'];
        }

        return '' !== $rebuiltUrl ? $rebuiltUrl : (string) $replacedUrl;
    }

    private function simpleReplace(string $tag, string $attribute, string $translatedPage, string $currentUrl, string $quote1, string $quote2, ?string $sometags = null): string
    {
        $currentLanguage = Plugin::getInstance()->getRequestUrlService()->getCurrentLanguage();
        $newUrl = $this->replaceUrl($currentUrl, $currentLanguage);
        $regex = '/<'.preg_quote($tag, '/').preg_quote((string) $sometags, '/').$attribute.'='.preg_quote($quote1.$currentUrl.$quote2, '/').'/';
        $replacement = '<'.$tag.$sometags.$attribute.'='.$quote1.$newUrl.$quote2;

        return preg_replace($regex, $replacement, $translatedPage);
    }

    public function replaceA(string $translatedPage, string $currentUrl, string $quote1, string $quote2, ?string $sometags = null, ?string $sometags2 = null): string
    {
        $currentLanguage = Plugin::getInstance()->getRequestUrlService()->getCurrentLanguage();
        $newUrl = $this->replaceUrl($currentUrl, $currentLanguage);
        $regex = '/<a'.preg_quote((string) $sometags, '/').'href='.preg_quote($quote1.$currentUrl.$quote2, '/').preg_quote((string) $sometags2, '/').'>/';
        $replacement = '<a'.$sometags.'href='.$quote1.$newUrl.$quote2.$sometags2.'>';

        return preg_replace($regex, $replacement, $translatedPage);
    }

    public function replaceDatalink(string $translatedPage, string $currentUrl, string $quote1, string $quote2, ?string $sometags = null): string
    {
        return $this->simpleReplace('', 'data-link', $translatedPage, $currentUrl, $quote1, $quote2, $sometags);
    }

    public function replaceDataurl(string $translatedPage, string $currentUrl, string $quote1, string $quote2, ?string $sometags = null): string
    {
        return $this->simpleReplace('', 'data-url', $translatedPage, $currentUrl, $quote1, $quote2, $sometags);
    }

    public function replaceDatacart(string $translatedPage, string $currentUrl, string $quote1, string $quote2, ?string $sometags = null): string
    {
        return $this->simpleReplace('', 'data-cart-url', $translatedPage, $currentUrl, $quote1, $quote2, $sometags);
    }

    public function replaceForm(string $translatedPage, string $currentUrl, string $quote1, string $quote2, ?string $sometags = null): string
    {
        $currentLanguage = Plugin::getInstance()->getRequestUrlService()->getCurrentLanguage();
        $newUrl = $this->replaceUrl($currentUrl, $currentLanguage, false);
        $regex = '/<form'.preg_quote((string) $sometags, '/').'action='.preg_quote($quote1.$currentUrl.$quote2, '/').'/';
        $replacement = '<form '.$sometags.'action='.$quote1.$newUrl.$quote2;

        return preg_replace($regex, $replacement, $translatedPage);
    }

    public function replaceCanonical(string $translatedPage, string $currentUrl, string $quote1, string $quote2, ?string $sometags = null): string
    {
        return $this->simpleReplace('link rel="canonical"', 'href', $translatedPage, $currentUrl, $quote1, $quote2, $sometags);
    }

    public function replaceNext(string $translatedPage, string $currentUrl, string $quote1, string $quote2, ?string $sometags = null): string
    {
        return $this->simpleReplace('link rel="next"', 'href', $translatedPage, $currentUrl, $quote1, $quote2, $sometags);
    }

    /**
     * @return string the modified page content with the updated "prev" link tag
     */
    public function replacePrev(string $translatedPage, string $currentUrl, string $quote1, string $quote2, ?string $sometags = null): string
    {
        return $this->simpleReplace('link rel="prev"', 'href', $translatedPage, $currentUrl, $quote1, $quote2, $sometags);
    }

    public function replaceAmp(string $translatedPage, string $currentUrl, string $quote1, string $quote2, ?string $sometags = null): string
    {
        return $this->simpleReplace('link rel="amphtml"', 'href', $translatedPage, $currentUrl, $quote1, $quote2, $sometags);
    }

    public function replaceMeta(string $translatedPage, string $currentUrl, string $quote1, string $quote2, ?string $sometags = null): string
    {
        return $this->simpleReplace('meta property="og:url"', 'content', $translatedPage, $currentUrl, $quote1, $quote2, $sometags);
    }
}
