<?php

namespace weglot\craftweglot\services;

use craft\base\Component;
use GuzzleHttp\Exception\RequestException;
use weglot\craftweglot\helpers\HelperApi;

class SlugService extends Component
{
    /**
     * @var array<string, array{forward: array<string,string>, reverse: array<string,string>}>|null
     */
    private ?array $slugsCache = null;

    /**
     * @var array<string, array{forward: array<string,string>, reverse: array<string,string>}>|null
     */
    private ?array $slugsFromApi = null;

    /**
     * @param array<int,string> $destinationLanguages
     *
     * @return array<string, array{forward: array<string,string>, reverse: array<string,string>}>
     */
    public function getSlugMapsFromCacheWithApiKey(string $apiKey, array $destinationLanguages): array
    {
        if (null !== $this->slugsCache) {
            return $this->slugsCache;
        }

        $destinationLanguages = array_values(array_unique(array_filter(array_map(strtolower(...), $destinationLanguages))));
        sort($destinationLanguages);

        $cacheKey = $this->buildSlugsCacheKey($apiKey, $destinationLanguages);

        $cached = \Craft::$app->getCache()->get($cacheKey);
        if (\is_array($cached)) {
            $this->slugsCache = $cached;

            return $this->slugsCache;
        }

        try {
            $maps = $this->getSlugMapsFromApiWithApiKey($apiKey, $destinationLanguages);
            $this->slugsCache = $maps;

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
        if (null !== $this->slugsFromApi) {
            return $this->slugsFromApi;
        }

        $destinationLanguages = array_values(array_unique(array_filter(array_map(strtolower(...), $destinationLanguages))));
        if ([] === $destinationLanguages) {
            $this->slugsFromApi = [];

            return $this->slugsFromApi;
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

        $this->slugsFromApi = $out;

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
     * @return string|null the translated redirect path if the first segment is successfully translated, or null if no translation is applicable
     */
    public function getRedirectPathIfUntranslated(string $apiKey, array $destinationLanguages, string $languageTo, string $pathWithoutLangPrefix): ?string
    {
        $pathWithoutLangPrefix = ltrim($pathWithoutLangPrefix, '/');
        if ('' === $pathWithoutLangPrefix) {
            return null;
        }

        $segments = explode('/', $pathWithoutLangPrefix);
        $first = $segments[0] ?? '';
        if ('' === $first) {
            return null;
        }

        $translated = $this->translateSlug($apiKey, $destinationLanguages, $languageTo, $first);
        if (null === $translated) {
            return null;
        }

        $segments[0] = $translated;

        return implode('/', $segments);
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
        $pathWithoutLangPrefix = ltrim($pathWithoutLangPrefix, '/');
        if ('' === $languageTo || '' === $pathWithoutLangPrefix) {
            return null;
        }

        $segments = explode('/', $pathWithoutLangPrefix);
        $first = $segments[0] ?? '';
        if ('' === $first) {
            return null;
        }

        $maps = $this->getSlugMapsFromCacheWithApiKey($apiKey, $destinationLanguages);
        $reverse = $maps[$languageTo]['reverse'] ?? null;
        if (!\is_array($reverse)) {
            return null;
        }

        $original = $reverse[$first] ?? null;
        if (!\is_string($original) || '' === $original) {
            return null;
        }

        $segments[0] = $original;

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

        $segments = explode('/', $after);
        $first = $segments[0] ?? '';
        if ('' === $first) {
            return $url;
        }

        // translateSlug(...) doit exister dans ton SlugService (mapping original => translated)
        $translated = $this->translateSlug($apiKey, $destinationLanguages, $languageToExternal, $first);
        if (null === $translated || '' === $translated || $translated === $first) {
            return $url;
        }

        $segments[0] = $translated;
        $newPath = $prefix.implode('/', $segments);

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
