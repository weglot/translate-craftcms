<?php

namespace Weglot\Util;

use Weglot\Client\Api\LanguageEntry;

/**
 * @phpstan-type ExcludedUrl = array{Regex, mixed}
 * @phpstan-type CustomUrl = array<string, string>
 */
class Url
{
    /**
     * @var string|null
     */
    protected $host;

    /**
     * @var string|null
     */
    protected $path;

    /**
     * @var string|null
     */
    protected $query;

    /**
     * @var string|null
     */
    protected $fragment;

    /**
     * @var array|null
     */
    protected $allUrls;

    /**
     * @var LanguageEntry
     */
    protected $currentLanguage;

    /**
     * @var string
     */
    protected $url;

    /**
     * @var LanguageEntry
     */
    protected $originalLanguage;

    /**
     * @var LanguageEntry[]
     */
    protected $destinationLanguages;

    /**
     * @var string
     */
    protected $pathPrefix;

    /**
     * @var array<ExcludedUrl>
     */
    protected $excludedUrls;

    /**
     * @var array<string, CustomUrl>
     */
    protected $customUrlsByLanguage;

    /**
     * @var string|null
     */
    protected $redirect;

    /**
     * @param string                   $url                  Current visited url
     * @param LanguageEntry            $originalLanguage     Default language represented by ISO 639-1 code
     * @param LanguageEntry[]          $destinationLanguages All available languages
     * @param string|null              $pathPrefix           Prefix to access website root path (ie. : `/my/custom/path`, don't forget: starting `/` and no ending `/`)
     * @param array<ExcludedUrl>       $excludedUrls         Array of excluded URL with regex and languages
     * @param array<string, CustomUrl> $customUrlsByLanguage Array of custom URLs (translated URLs)
     */
    public function __construct($url, $originalLanguage, $destinationLanguages, $pathPrefix, $excludedUrls, $customUrlsByLanguage)
    {
        $this->url = $url;
        $this->originalLanguage = $originalLanguage;
        $this->destinationLanguages = $destinationLanguages;
        $this->pathPrefix = $pathPrefix;
        $this->excludedUrls = $excludedUrls;
        $this->customUrlsByLanguage = $customUrlsByLanguage;
        $this->detectUrlDetails();
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Sets the full URL.
     *
     * @param string $url
     *
     * @return void
     */
    public function setUrl($url)
    {
        $this->url = $url;
        $this->detectUrlDetails();
    }

    /**
     * @return string|null
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return string|null
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getPathPrefix()
    {
        return $this->pathPrefix;
    }

    /**
     * @param array<ExcludedUrl> $excludedUrls
     *
     * @return $this
     */
    public function setExcludedUrls($excludedUrls)
    {
        $this->excludedUrls = $excludedUrls;

        return $this;
    }

    /**
     * @return LanguageEntry
     */
    public function getDefault()
    {
        return $this->originalLanguage;
    }

    /**
     * @return string|null
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @return string|null
     */
    public function getFragment()
    {
        return $this->fragment;
    }

    /**
     * @return string|null
     */
    public function getPathAndQuery()
    {
        $pathAndQuery = $this->path;
        if (null !== $this->getQuery()) {
            $pathAndQuery .= '?'.$this->getQuery();
        }

        return $pathAndQuery;
    }

    /**
     * @return string|null
     */
    public function getRedirect()
    {
        return $this->redirect;
    }

    /**
     * Returns the destination languages external codes.
     *
     * @return string[]
     */
    public function getDestinationLanguagesExternal()
    {
        return array_map(static function ($l) { return $l->getExternalCode(); }, $this->destinationLanguages);
    }

    /**
     * @param LanguageEntry $language
     * @param bool          $evenExcluded
     *
     * @return bool|string
     */
    public function getForLanguage($language, $evenExcluded = false)
    {
        $urls = $this->getAllUrls();
        foreach ($urls as $url) {
            if ($url['language'] === $language) {
                if (!$evenExcluded && $url['excluded']) {
                    return false;
                }

                return $url['url'];
            }
        }

        return false;
    }

    /**
     * @param LanguageEntry $language
     * @param string        $option
     *
     * @return bool|string
     */
    public function getExcludeOption($language, $option)
    {
        $urls = $this->getAllUrls();
        foreach ($urls as $url) {
            if ($url['language'] === $language) {
                switch ($option) {
                    case 'language_button_displayed':
                        if ($url[$option]) {
                            return true;
                        }
                        break;
                    case 'exclusion_behavior':
                        if ('REDIRECT' == $url[$option]) {
                            return true;
                        } elseif ('NOT_TRANSLATED' == $url[$option]) {
                            return false;
                        } elseif ('NOT_FOUND' == $url[$option]) {
                            return 'NOT_FOUND';
                        }
                        break;
                }
            }
        }

        return false;
    }

    /**
     * Check if we need to translate given URL.
     *
     * @param LanguageEntry $language
     * @param bool          $evenExcluded
     *
     * @return bool
     */
    public function isTranslableInLanguage($language, $evenExcluded = false)
    {
        if ($this->getForLanguage($language, $evenExcluded)) {
            return true;
        }

        return false;
    }

    /**
     * Check if we need to translate given URL.
     *
     * @param bool $evenExcluded
     *
     * @return array
     */
    public function availableInLanguages($evenExcluded)
    {
        $availableLanguage = [];
        foreach ($this->destinationLanguages as $destinationLanguage) {
            if ($this->getForLanguage($destinationLanguage, $evenExcluded)) {
                $availableLanguage[] = $destinationLanguage;
            }
        }

        return $availableLanguage;
    }

    /**
     * Check current locale, based on URI segments from the given URL.
     *
     * @return LanguageEntry
     */
    public function getCurrentLanguage()
    {
        return $this->currentLanguage;
    }

    /**
     * Generate possible host & base URL then store it into internal variables.
     *
     * @return string|null Host + path prefix + base URL
     */
    public function detectUrlDetails()
    {
        if (\defined('WP_CLI') && WP_CLI) {
            return null;
        }

        $escapedPathPrefix = Text::escapeForRegex($this->pathPrefix);

        $urlNoPrefix = preg_replace('#'.$escapedPathPrefix.'#i', '', $this->getUrl(), 1);

        $uriPath = parse_url($urlNoPrefix, \PHP_URL_PATH);
        $uriSegments = [];
        if (\is_string($uriPath)) {
            $uriSegments = explode('/', $uriPath);
        }

        if (isset($uriSegments[1]) && \in_array($uriSegments[1], $this->getDestinationLanguagesExternal())) {
            foreach ($this->destinationLanguages as $language) {
                if ($language->getExternalCode() === $uriSegments[1]) {
                    $this->currentLanguage = $language;
                }
            }
        } else {
            $this->currentLanguage = $this->originalLanguage;
        }

        $urlNoPrefixNoLanguage = str_replace('/'.$this->currentLanguage->getExternalCode().'/', '/', $urlNoPrefix);

        $parsed = parse_url($urlNoPrefixNoLanguage);

        if (isset($parsed['scheme']) && isset($parsed['host'])) {
            $this->host = $parsed['scheme'].'://'.$parsed['host'].(isset($parsed['port']) ? ':'.$parsed['port'] : '');
        }
        $this->path = isset($parsed['path']) ? urldecode($parsed['path']) : '/';
        $this->query = $parsed['query'] ?? null;
        $this->fragment = $parsed['fragment'] ?? null;

        if ('' === $this->path) {
            $this->path = '/';
        }

        // We need to change the path to the original path if there are custom URL
        if (isset($this->customUrlsByLanguage[$this->currentLanguage->getInternalCode()])) {
            $slugs = explode('/', $this->path);
            $fully_translated_slug_array = [];
            $mustRedirect = false;
            foreach ($slugs as $k => $slug) {
                $fully_translated_slug_array[] = $slug;

                if (empty($slug)) {
                    continue;
                }

                foreach ($this->customUrlsByLanguage[$this->currentLanguage->getInternalCode()] as $translatedURL => $originalURL) {
                    if ($slug === $originalURL) {
                        $mustRedirect = true;
                        array_pop($fully_translated_slug_array);
                        $fully_translated_slug_array[] = $translatedURL; // = "301_" . $translatedURL; //If we receive a not translated slug we return a 404. For example if we have /fr/products but should have /fr/produits we should have a 404
                    }
                    if ($slug === $translatedURL) {
                        $slugs[$k] = $originalURL;
                    }
                }
            }

            if ($mustRedirect) {
                $this->redirect = implode('/', $fully_translated_slug_array);
            }
            $this->path = implode('/', $slugs);
        }

        $url = $this->getHost().$this->getPathPrefix().$this->getPath();
        if (null !== $this->getQuery()) {
            $url .= '?'.$this->getQuery();
        }

        if (null !== $this->getFragment()) {
            $url .= '#'.$this->getFragment();
        }

        return $url;
    }

    /**
     * Returns advance excluded option button displayed.
     *
     * @param array $excludedUrl
     *
     * @return bool
     */
    public function exclusionBehavior($excludedUrl)
    {
        $exclusionBehavior = 'NOT_TRANSLATED';
        if (isset($excludedUrl[2])) {
            $exclusionBehavior = $excludedUrl[2];
        }

        return $exclusionBehavior;
    }

    /**
     * Returns advance excluded option button displayed.
     *
     * @param array $excludedUrl
     *
     * @return bool
     */
    public function languageButtonDisplayed($excludedUrl)
    {
        $languageButtonDisplayed = true;
        if (isset($excludedUrl[3]) && false === $excludedUrl[3]) {
            $languageButtonDisplayed = false;
        }

        return $languageButtonDisplayed;
    }

    /**
     * Returns array with all possible URL for current Request.
     *
     * @return array
     */
    public function getAllUrls()
    {
        if (\defined('WP_CLI') && WP_CLI) {
            return [];
        }

        $urls = $this->allUrls;

        if (null === $urls) {
            $urls = [];
            $originalURL = $this->getHost().$this->getPathPrefix().$this->getPath();
            if (null !== $this->getQuery()) {
                $originalURL .= '?'.$this->getQuery();
            }
            if (null !== $this->getFragment()) {
                $originalURL .= '#'.$this->getFragment();
            }

            $languageButtonDisplayed = true;
            $exclusionBehavior = 'NOT_TRANSLATED';

            foreach ($this->excludedUrls as $excludedUrl) {
                $regex = $excludedUrl[0];
                if ($regex->match($this->getPath())) {
                    $exclusionBehavior = $this->exclusionBehavior($excludedUrl);
                    $languageButtonDisplayed = $this->languageButtonDisplayed($excludedUrl);
                }
            }

            $urls[] = ['language' => $this->originalLanguage, 'url' => $originalURL, 'excluded' => false, 'exclusion_behavior' => $exclusionBehavior, 'language_button_displayed' => $languageButtonDisplayed];

            foreach ($this->destinationLanguages as $language) {
                $isExcluded = false;
                $languageButtonDisplayed = true;
                $exclusionBehavior = 'NOT_TRANSLATED';
                foreach ($this->excludedUrls as $excludedUrl) {
                    $regex = $excludedUrl[0];
                    if (null === $excludedUrl[1] || (\is_array($excludedUrl[1]) && \in_array($language, $excludedUrl[1]))) {
                        if ($regex->match($this->getPath())) {
                            $isExcluded = true;
                            $exclusionBehavior = $this->exclusionBehavior($excludedUrl);
                            $languageButtonDisplayed = $this->languageButtonDisplayed($excludedUrl);
                            break;
                        }
                    }
                }

                $translatedPath = $this->getPath();
                if (isset($this->customUrlsByLanguage[$language->getInternalCode()])) {
                    $slugs = explode('/', $this->path);
                    foreach ($slugs as $k => $slug) {
                        if (empty($slug)) {
                            continue;
                        }

                        foreach ($this->customUrlsByLanguage[$language->getInternalCode()] as $translatedURL => $originalURL) {
                            if ($slug === $originalURL) {
                                $slugs[$k] = $translatedURL;
                            }
                        }
                    }
                    $translatedPath = implode('/', $slugs);
                }
                $url = $this->getHost().$this->getPathPrefix().'/'.$language->getExternalCode().$translatedPath;
                if (null !== $this->getQuery()) {
                    $url .= '?'.$this->getQuery();
                }
                if (null !== $this->getFragment()) {
                    $url .= '#'.$this->getFragment();
                }
                $urls[] = ['language' => $language, 'url' => $url, 'excluded' => $isExcluded, 'exclusion_behavior' => $exclusionBehavior, 'language_button_displayed' => $languageButtonDisplayed];
            }

            $this->allUrls = $urls;
        }

        return $urls;
    }
}
