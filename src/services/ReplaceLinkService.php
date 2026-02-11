<?php

namespace weglot\craftweglot\services;

use craft\base\Component;
use weglot\craftweglot\Plugin;
use Weglot\Vendor\Weglot\Client\Api\LanguageEntry;

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

    /**
     * @param string        $url          the original URL to be processed and potentially replaced
     * @param LanguageEntry $language     the target language for which the URL should be adapted
     * @param bool          $evenExcluded whether to include languages marked as excluded during URL replacement
     *
     * @return string the updated URL adapted for the specified language or the original URL if no modifications were made
     */
    public function replaceUrl(string $url, LanguageEntry $language, bool $evenExcluded = true): string
    {
        $weglotUrl = $this->requestUrlService->createUrlObject($url);
        $replacedUrl = $weglotUrl->getForLanguage($language, $evenExcluded);

        if (!$replacedUrl) {
            return $url;
        }

        $parsedUrl = parse_url($replacedUrl);

        if (isset($parsedUrl['path']) && '' !== $parsedUrl['path'] && '0' !== $parsedUrl['path'] && !str_ends_with($parsedUrl['path'], '/')) {
            $parsedUrl['path'] .= '/';
        }

        $rebuiltUrl = '';
        if (isset($parsedUrl['scheme'], $parsedUrl['host'])) {
            $rebuiltUrl .= $parsedUrl['scheme'].'://'.$parsedUrl['host'];
        }
        if (isset($parsedUrl['port'])) {
            $rebuiltUrl .= ':'.$parsedUrl['port'];
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
        $finalUrl = '' !== $rebuiltUrl ? $rebuiltUrl : (string) $replacedUrl;

        try {
            $settings = Plugin::getInstance()->getTypedSettings();
            $apiKey = trim((string) $settings->apiKey);
            $langExternal = strtolower(trim((string) $language->getExternalCode()));

            if ('' !== $apiKey) {
                $languageService = Plugin::getInstance()->getLanguage();
                $destinationEntries = $languageService->getDestinationLanguages();
                $destinationCodes = $languageService->codesFromDestinationEntries($destinationEntries, true);

                if ([] !== $destinationCodes) {
                    $finalUrl = Plugin::getInstance()->getSlug()->translateUrlForLanguage(
                        $apiKey,
                        [$langExternal],
                        $language->getExternalCode(),
                        $finalUrl
                    );
                }
            }
        } catch (\Throwable $e) {
            \Craft::warning('replaceUrl slug translation failed: '.$e->getMessage(), __METHOD__);
        }

        return $finalUrl;
    }

    /**
     * @param string      $tag            the HTML tag to search for
     * @param string      $attribute      the attribute of the tag to modify
     * @param string      $translatedPage the content of the page where the replacement should occur
     * @param string      $currentUrl     the current URL to be replaced
     * @param string      $quote1         the opening quote around the URL in the attribute
     * @param string      $quote2         the closing quote around the URL in the attribute
     * @param string|null $sometags       additional tag attributes or content, if any
     *
     * @return string the modified page content with the URL replaced
     */
    private function simpleReplace(string $tag, string $attribute, string $translatedPage, string $currentUrl, string $quote1, string $quote2, ?string $sometags = null): string
    {
        $currentLanguage = Plugin::getInstance()->getRequestUrlService()->getCurrentLanguage();
        $newUrl = $this->replaceUrl($currentUrl, $currentLanguage);
        $regex = '/<'.preg_quote($tag, '/').preg_quote((string) $sometags, '/').$attribute.'='.preg_quote($quote1.$currentUrl.$quote2, '/').'/';
        $replacement = '<'.$tag.$sometags.$attribute.'='.$quote1.$newUrl.$quote2;

        return preg_replace($regex, $replacement, $translatedPage);
    }

    /**
     * @param string      $translatedPage the translated page content where the replacements will occur
     * @param string      $currentUrl     the current URL to be matched and replaced
     * @param string      $quote1         the opening quote for the URL in the anchor tag
     * @param string      $quote2         the closing quote for the URL in the anchor tag
     * @param string|null $sometags       optional additional attributes or tags to match before the href in the anchor tag
     * @param string|null $sometags2      optional additional attributes or tags to match after the URL in the anchor tag
     *
     * @return string the updated page content with the anchor tag URLs replaced
     */
    public function replaceA(string $translatedPage, string $currentUrl, string $quote1, string $quote2, ?string $sometags = null, ?string $sometags2 = null): string
    {
        $currentLanguage = Plugin::getInstance()->getRequestUrlService()->getCurrentLanguage();
        $newUrl = $this->replaceUrl($currentUrl, $currentLanguage);
        $regex = '/<a'.preg_quote((string) $sometags, '/').'href='.preg_quote($quote1.$currentUrl.$quote2, '/').preg_quote((string) $sometags2, '/').'>/';
        $replacement = '<a'.$sometags.'href='.$quote1.$newUrl.$quote2.$sometags2.'>';

        return preg_replace($regex, $replacement, $translatedPage);
    }

    /**
     * @param string      $translatedPage the translated page content where the replacement will occur
     * @param string      $currentUrl     the current URL to use in the replacement
     * @param string      $quote1         the first quote to surround the replacement value
     * @param string      $quote2         the second quote to surround the replacement value
     * @param string|null $sometags       additional optional tags to include in the replacement process
     *
     * @return string the processed page content with data-link replaced
     */
    public function replaceDatalink(string $translatedPage, string $currentUrl, string $quote1, string $quote2, ?string $sometags = null): string
    {
        return $this->simpleReplace('', 'data-link', $translatedPage, $currentUrl, $quote1, $quote2, $sometags);
    }

    /**
     * @param string      $translatedPage the content of the translated page where replacements will occur
     * @param string      $currentUrl     the current URL used for the replacement logic
     * @param string      $quote1         the first quote character used in the replacement process
     * @param string      $quote2         the second quote character used in the replacement process
     * @param string|null $sometags       optional additional tags to be used in the replacement
     *
     * @return string the modified content of the translated page with replaced data URLs
     */
    public function replaceDataurl(string $translatedPage, string $currentUrl, string $quote1, string $quote2, ?string $sometags = null): string
    {
        return $this->simpleReplace('', 'data-url', $translatedPage, $currentUrl, $quote1, $quote2, $sometags);
    }

    /**
     * @param string      $translatedPage the content of the translated page where replacements will be applied
     * @param string      $currentUrl     the current URL that is used in the replacement logic
     * @param string      $quote1         the first quote character utilized in the replacement process
     * @param string      $quote2         the second quote character utilized in the replacement process
     * @param string|null $sometags       optional additional tags to include in the replacement logic
     *
     * @return string the updated content of the translated page with data cart URLs replaced
     */
    public function replaceDatacart(string $translatedPage, string $currentUrl, string $quote1, string $quote2, ?string $sometags = null): string
    {
        return $this->simpleReplace('', 'data-cart-url', $translatedPage, $currentUrl, $quote1, $quote2, $sometags);
    }

    /**
     * @param string      $translatedPage the content of the translated page where form action URLs will be replaced
     * @param string      $currentUrl     the current URL to be replaced in the form action attribute
     * @param string      $quote1         the first quote character used around the URL in the form action attribute
     * @param string      $quote2         the second quote character used around the URL in the form action attribute
     * @param string|null $sometags       optional additional attributes or tags to be included within the form element
     *
     * @return string the modified content of the translated page with updated form action URLs
     */
    public function replaceForm(string $translatedPage, string $currentUrl, string $quote1, string $quote2, ?string $sometags = null): string
    {
        $currentLanguage = Plugin::getInstance()->getRequestUrlService()->getCurrentLanguage();
        $newUrl = $this->replaceUrl($currentUrl, $currentLanguage, false);
        $regex = '/<form'.preg_quote((string) $sometags, '/').'action='.preg_quote($quote1.$currentUrl.$quote2, '/').'/';
        $replacement = '<form '.$sometags.'action='.$quote1.$newUrl.$quote2;

        return preg_replace($regex, $replacement, $translatedPage);
    }

    /**
     * Replaces the canonical link's href attribute in the given translated page.
     *
     * @param string      $translatedPage the content of the translated page where the canonical link should be replaced
     * @param string      $currentUrl     the URL to set as the new href value in the canonical link
     * @param string      $quote1         the opening quote character to wrap the href value
     * @param string      $quote2         the closing quote character to wrap the href value
     * @param string|null $sometags       additional tags or attributes, if any, to include in the replacement process
     *
     * @return string the modified content of the translated page with the new canonical link
     */
    public function replaceCanonical(string $translatedPage, string $currentUrl, string $quote1, string $quote2, ?string $sometags = null): string
    {
        return $this->simpleReplace('link rel="canonical"', 'href', $translatedPage, $currentUrl, $quote1, $quote2, $sometags);
    }

    /**
     * Replaces the next link's href attribute in the given translated page.
     *
     * @param string      $translatedPage the content of the translated page where the next link should be replaced
     * @param string      $currentUrl     the URL to set as the new href value in the next link
     * @param string      $quote1         the opening quote character to wrap the href value
     * @param string      $quote2         the closing quote character to wrap the href value
     * @param string|null $sometags       additional tags or attributes, if any, to include in the replacement process
     *
     * @return string the modified content of the translated page with the new next link
     */
    public function replaceNext(string $translatedPage, string $currentUrl, string $quote1, string $quote2, ?string $sometags = null): string
    {
        return $this->simpleReplace('link rel="next"', 'href', $translatedPage, $currentUrl, $quote1, $quote2, $sometags);
    }

    /**
     * Replaces the previous link's href attribute in the given translated page.
     *
     * @param string      $translatedPage the content of the translated page where the previous link should be replaced
     * @param string      $currentUrl     the URL to set as the new href value in the previous link
     * @param string      $quote1         the opening quote character to wrap the href value
     * @param string      $quote2         the closing quote character to wrap the href value
     * @param string|null $sometags       additional tags or attributes, if any, to include in the replacement process
     *
     * @return string the modified content of the translated page with the new previous link
     */
    public function replacePrev(string $translatedPage, string $currentUrl, string $quote1, string $quote2, ?string $sometags = null): string
    {
        return $this->simpleReplace('link rel="prev"', 'href', $translatedPage, $currentUrl, $quote1, $quote2, $sometags);
    }

    /**
     * @param string      $translatedPage the content of the translated page where the amphtml link should be replaced
     * @param string      $currentUrl     the URL to set as the new href value in the amphtml link
     * @param string      $quote1         the opening quote character to wrap the href value
     * @param string      $quote2         the closing quote character to wrap the href value
     * @param string|null $sometags       additional tags or attributes, if any, to include in the replacement process
     *
     * @return string the modified content of the translated page with the new amphtml link
     */
    public function replaceAmp(string $translatedPage, string $currentUrl, string $quote1, string $quote2, ?string $sometags = null): string
    {
        return $this->simpleReplace('link rel="amphtml"', 'href', $translatedPage, $currentUrl, $quote1, $quote2, $sometags);
    }

    /**
     * @param string      $translatedPage the content of the translated page where the meta tag should be replaced
     * @param string      $currentUrl     the URL to set as the new content value in the meta tag
     * @param string      $quote1         the opening quote character to wrap the content value
     * @param string      $quote2         the closing quote character to wrap the content value
     * @param string|null $sometags       additional tags or attributes, if any, to include in the replacement process
     *
     * @return string the modified content of the translated page with the updated "og:url" meta tag
     */
    public function replaceMeta(string $translatedPage, string $currentUrl, string $quote1, string $quote2, ?string $sometags = null): string
    {
        return $this->simpleReplace('meta property="og:url"', 'content', $translatedPage, $currentUrl, $quote1, $quote2, $sometags);
    }
}
