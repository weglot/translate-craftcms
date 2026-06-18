<?php

declare(strict_types=1);

namespace weglot\craftweglot\services;

use craft\base\Component;
use GuzzleHttp\Exception\RequestException;
use weglot\craftweglot\helpers\HelperApi;

class SlugService extends Component
{
    /**
     * @var array<string, array<string, array{forward: array<string,string>, reverse: array<string,string>}>>
     */
    private array $slugsCache = [];

    /**
     * @var array<string, array<string, array{forward: array<string,string>, reverse: array<string,string>}>>
     */
    private array $slugsFromApi = [];

    /**
     * @param array<int,string> $destinationLanguages
     *
     * @return array<string, array{forward: array<string,string>, reverse: array<string,string>}>
     */
    public function getSlugMapsFromCacheWithApiKey(string $apiKey, array $destinationLanguages): array
    {
        $destinationLanguages = array_values(array_unique(array_filter(array_map(strtolower(...), $destinationLanguages))));
        sort($destinationLanguages);

        $memoKey = implode(',', $destinationLanguages);
        if (isset($this->slugsCache[$memoKey])) {
            return $this->slugsCache[$memoKey];
        }

        $cacheKey = $this->buildSlugsCacheKey($apiKey, $destinationLanguages);

        $cached = \Craft::$app->getCache()->get($cacheKey);
        if (\is_array($cached)) {
            $this->slugsCache[$memoKey] = $cached;

            return $cached;
        }

        try {
            $maps = $this->getSlugMapsFromApiWithApiKey($apiKey, $destinationLanguages);
            $this->slugsCache[$memoKey] = $maps;

            $ttl = 0; // à ajuster si tu veux une expiration
            \Craft::$app->getCache()->set($cacheKey, $maps, $ttl);

            return $maps;
        } catch (\Throwable $e) {
            \Craft::warning('Error retrieving Weglot slugs: '.$e->getMessage(), __METHOD__);

            return [];
        }
    }

    /**
     * @param array<int,string> $destinationLanguages
     *
     * @return array<string, array{forward: array<string,string>, reverse: array<string,string>}>
     */
    public function getSlugMapsFromApiWithApiKey(string $apiKey, array $destinationLanguages): array
    {
        $destinationLanguages = array_values(array_unique(array_filter(array_map(strtolower(...), $destinationLanguages))));
        sort($destinationLanguages);

        if ([] === $destinationLanguages) {
            return [];
        }

        $memoKey = implode(',', $destinationLanguages);
        if (isset($this->slugsFromApi[$memoKey])) {
            return $this->slugsFromApi[$memoKey];
        }

        $options = \Craft::$app->getCache()->get('weglot_cache_cdn');
        $slugTranslationVersion = null;
        if (\is_array($options) && isset($options['versions']) && \is_array($options['versions'])) {
            $slugTranslationVersion = $options['versions']['slugTranslation'] ?? null;
        }

        $client = \Craft::createGuzzleClient();

        $out = [];
        foreach ($destinationLanguages as $languageTo) {
            $url = \sprintf(
                '%s/translations/slugs?api_key=%s&language_to=%s%s',
                HelperApi::getApiUrl(),
                rawurlencode($apiKey),
                rawurlencode($languageTo),
                (null !== $slugTranslationVersion && '' !== (string) $slugTranslationVersion)
                    ? '&v='.rawurlencode((string) $slugTranslationVersion)
                    : ''
            );

            try {
                $response = $client->request('GET', $url, ['timeout' => 3]);
                $body = json_decode((string) $response->getBody(), true);

                if (!\is_array($body)) {
                    continue;
                }

                // forward: original => translated
                $forward = [];
                foreach ($body as $original => $translated) {
                    if (!\is_string($original) || !\is_string($translated)) {
                        continue;
                    }
                    $original = trim($original);
                    $translated = trim($translated);

                    if ('' === $original || '' === $translated || $original === $translated) {
                        continue;
                    }
                    $forward[$original] = $translated;
                }

                // reverse: translated => original
                $reverse = array_flip($forward);

                $out[$languageTo] = [
                    'forward' => $forward,
                    'reverse' => \is_array($reverse) ? $reverse : [],
                ];
            } catch (RequestException $e) {
                \Craft::warning('Weglot slugs API request failed: '.$e->getMessage(), __METHOD__);
                continue;
            } catch (\Throwable $e) {
                \Craft::warning('Weglot slugs decode failed: '.$e->getMessage(), __METHOD__);
                continue;
            }
        }

        $this->slugsFromApi[$memoKey] = $out;

        return $out;
    }

    /**
     * @param string             $apiKey               API key used to retrieve slug mappings
     * @param array<int, string> $destinationLanguages the list of destination languages for which slug mappings exist
     * @param string             $languageTo           the target language code for the translation
     * @param string             $originalSlug         the original slug to be translated
     *
     * @return string|null the translated slug if found, or null if translation is not available
     */
    public function translateSlug(string $apiKey, array $destinationLanguages, string $languageTo, string $originalSlug): ?string
    {
        $languageTo = strtolower(trim($languageTo));
        $originalSlug = trim($originalSlug);
        if ('' === $languageTo || '' === $originalSlug) {
            return null;
        }

        $maps = $this->getSlugMapsFromCacheWithApiKey($apiKey, $destinationLanguages);
        $forward = $maps[$languageTo]['forward'] ?? null;
        if (!\is_array($forward)) {
            return null;
        }

        return $forward[$originalSlug] ?? null;
    }

    /**
     * @param string            $apiKey                the API key used for accessing the slug translation service
     * @param array<int,string> $destinationLanguages  an array of destination languages for translation, represented as language codes
     * @param string            $languageTo            the target language code for the translation
     * @param string            $pathWithoutLangPrefix the path to be processed, without any language prefix
     *
     * @return string|null the translated path when at least one segment is translated, or null when no segment matches a slug mapping
     */
    public function getRedirectPathIfUntranslated(string $apiKey, array $destinationLanguages, string $languageTo, string $pathWithoutLangPrefix): ?string
    {
        $languageTo = strtolower(trim($languageTo));
        if ('' === $languageTo) {
            return null;
        }

        $maps = $this->getSlugMapsFromCacheWithApiKey($apiKey, $destinationLanguages);
        $forward = $maps[$languageTo]['forward'] ?? null;
        if (!\is_array($forward)) {
            return null;
        }

        return $this->translatePathSegments($pathWithoutLangPrefix, $forward);
    }

    /**
     * @param array<int,string> $destinationLanguages
     */
    private function buildSlugsCacheKey(string $apiKey, array $destinationLanguages): string
    {
        $options = \Craft::$app->getCache()->get('weglot_cache_cdn');
        $v = null;
        if (\is_array($options) && isset($options['versions']) && \is_array($options['versions'])) {
            $v = $options['versions']['slugTranslation'] ?? null;
        }

        $raw = json_encode([
            'apiKey' => $apiKey,
            'langs' => $destinationLanguages,
            'v' => $v,
        ]);

        return 'weglot_slugs_cache_'.substr(sha1((string) $raw), 0, 24);
    }

    /**
     * @param string            $apiKey                the API key used for slug translation retrieval
     * @param array<int,string> $destinationLanguages  the list of destination languages for translation
     * @param string            $languageTo            the target language for which the slug is translated
     * @param string            $pathWithoutLangPrefix the path without a language prefix
     *
     * @return string|null the resolved internal path if a translation is found; otherwise, null
     */
    public function getInternalPathIfTranslatedSlug(string $apiKey, array $destinationLanguages, string $languageTo, string $pathWithoutLangPrefix): ?string
    {
        $languageTo = strtolower(trim($languageTo));
        if ('' === $languageTo) {
            return null;
        }

        $maps = $this->getSlugMapsFromCacheWithApiKey($apiKey, $destinationLanguages);
        $reverse = $maps[$languageTo]['reverse'] ?? null;
        if (!\is_array($reverse)) {
            return null;
        }

        return $this->translatePathSegments($pathWithoutLangPrefix, $reverse);
    }

    /**
     * Translate every path segment that matches a key in the given slug map.
     *
     * Slug maps are keyed by individual slugs (not full paths), so a nested URI
     * such as `blog/my-article` must be matched segment by segment rather than on
     * its first segment only.
     *
     * @param array<string, string> $map original|translated slug mapping
     *
     * @return string|null the rewritten path, or null when no segment matched
     */
    private function translatePathSegments(string $pathWithoutLangPrefix, array $map): ?string
    {
        $pathWithoutLangPrefix = ltrim($pathWithoutLangPrefix, '/');
        if ('' === $pathWithoutLangPrefix) {
            return null;
        }

        $segments = explode('/', $pathWithoutLangPrefix);
        $changed = false;
        foreach ($segments as $i => $segment) {
            if ('' === $segment) {
                continue;
            }
            if (isset($map[$segment]) && '' !== $map[$segment]) {
                $segments[$i] = $map[$segment];
                $changed = true;
            }
        }

        if (!$changed) {
            return null;
        }

        return implode('/', $segments);
    }

    /**
     * @param string             $apiKey               the API key used for fetching the translation mapping
     * @param array<int, string> $destinationLanguages list of destination languages for which translations are supported
     * @param string             $languageToExternal   The language to which the first path segment should correspond (e.g., `en`, `fr`).
     * @param string             $url                  the URL to be translated
     *
     * @return string the translated URL or the original URL if no translation is applied
     */
    public function translateUrlForLanguage(string $apiKey, array $destinationLanguages, string $languageToExternal, string $url): string
    {
        $languageToExternal = strtolower(trim($languageToExternal));
        if ('' === $languageToExternal || '' === trim($url)) {
            return $url;
        }

        $parsed = parse_url($url);
        if (false === $parsed) {
            return $url;
        }

        $path = $parsed['path'] ?? null;
        if (!\is_string($path) || '' === $path) {
            return $url;
        }

        $prefix = '/'.$languageToExternal.'/';
        if (!str_starts_with($path, $prefix)) {
            return $url;
        }

        $after = substr($path, \strlen($prefix));
        $after = ltrim($after, '/');
        if ('' === $after) {
            return $url;
        }

        $maps = $this->getSlugMapsFromCacheWithApiKey($apiKey, $destinationLanguages);
        $forward = $maps[$languageToExternal]['forward'] ?? null;
        if (!\is_array($forward)) {
            return $url;
        }

        $translatedAfter = $this->translatePathSegments($after, $forward);
        if (null === $translatedAfter || '' === $translatedAfter) {
            return $url;
        }

        $newPath = $prefix.$translatedAfter;

        if (isset($parsed['scheme'], $parsed['host'])) {
            $rebuilt = $parsed['scheme'].'://'.$parsed['host'];
            if (isset($parsed['port'])) {
                $rebuilt .= ':'.$parsed['port'];
            }
            $rebuilt .= $newPath;
            if (isset($parsed['query']) && '' !== $parsed['query']) {
                $rebuilt .= '?'.$parsed['query'];
            }
            if (isset($parsed['fragment']) && '' !== $parsed['fragment']) {
                $rebuilt .= '#'.$parsed['fragment'];
            }

            return $rebuilt;
        }

        $rebuilt = $newPath;
        if (isset($parsed['query']) && '' !== $parsed['query']) {
            $rebuilt .= '?'.$parsed['query'];
        }
        if (isset($parsed['fragment']) && '' !== $parsed['fragment']) {
            $rebuilt .= '#'.$parsed['fragment'];
        }

        return $rebuilt;
    }
}
