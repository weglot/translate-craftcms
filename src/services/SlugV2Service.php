<?php

declare(strict_types=1);

namespace weglot\craftweglot\services;

use craft\base\Component;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use weglot\craftweglot\helpers\HelperApi;
use weglot\craftweglot\Plugin;
use Weglot\Vendor\Weglot\Client\Api\LanguageEntry;

/**
 * Translated slugs of Weglot 2 projects, in the [external code => {forward, reverse}] shape of the
 * Weglot 1 maps of SlugService, so routing, links, the switcher and hreflang need no change.
 *
 * Slugs are cached per language and served even when outdated: one request refreshes them under a
 * lock, the others keep the previous slugs instead of waiting on the API.
 *
 * @phpstan-type SlugEntry array{version: string|null, fetched_at: int, retry_after: int, slugs: array<string, string>, redirects: array<string, string>}
 * @phpstan-type SlugMaps array{forward: array<string, string>, reverse: array<string, string>, redirects: array<string, string>}
 */
class SlugV2Service extends Component
{
    public const ROUTE = '/slug-translations/per-slug';

    /**
     * Refreshed at least daily: the API may not bump versions.slugTranslations when only a redirect
     * changes.
     */
    private const MAX_AGE = 86400;

    private const RETRY_DELAY = 300;

    /**
     * Seconds one request may spend fetching languages; the rest waits for the next request.
     */
    private const FETCH_BUDGET = 5;

    private const REQUEST_TIMEOUT = 3.0;

    /**
     * @var array<string, SlugMaps>|null
     */
    private ?array $maps = null;

    public static function cacheKey(string $apiKey): string
    {
        return 'weglot_slugs_v2_'.substr(sha1($apiKey), 0, 16);
    }

    /**
     * @param array<int, string> $externalCodes
     *
     * @return array<string, SlugMaps> keyed by lowercased external code
     */
    public function getSlugMaps(string $apiKey, array $externalCodes): array
    {
        $this->maps ??= $this->loadMaps($apiKey);

        $maps = [];
        foreach ($externalCodes as $code) {
            $code = strtolower($code);
            if (isset($this->maps[$code])) {
                $maps[$code] = $this->maps[$code];
            }
        }

        return $maps;
    }

    protected function createHttpClient(): ClientInterface
    {
        return \Craft::createGuzzleClient();
    }

    /**
     * @return array<string, SlugMaps>
     */
    private function loadMaps(string $apiKey): array
    {
        // Not gated on auto_slug_translation_enabled: that setting only drives the AI translation of
        // new slugs, and manual or earlier translations stay served when it is off.
        if (!HelperApi::isV2ApiKey($apiKey)) {
            return [];
        }

        $languages = $this->destinationLanguages();
        if ([] === $languages) {
            return [];
        }

        $entries = $this->loadEntries($apiKey, array_keys($languages));

        $maps = [];
        foreach ($languages as $apiCode => $externalCode) {
            if (isset($entries[$apiCode])) {
                $maps[$externalCode] = $this->buildMaps($entries[$apiCode]);
            }
        }

        return $maps;
    }

    /**
     * @return array<string, string> API language code => lowercased external code
     */
    private function destinationLanguages(): array
    {
        $languages = [];
        foreach (Plugin::getInstance()->getLanguage()->getDestinationLanguages() as $entry) {
            $externalCode = '' !== $entry->getExternalCode() ? $entry->getExternalCode() : $entry->getInternalCode();
            $languages[$this->apiLanguageCode($entry)] = strtolower($externalCode);
        }

        return $languages;
    }

    /**
     * The API expects Weglot's internal code, even when the site shows a custom one (`/br/` for
     * `pt-br`). Not yet confirmed by real-world tests: change it here only.
     */
    private function apiLanguageCode(LanguageEntry $entry): string
    {
        return $entry->getInternalCode();
    }

    /**
     * @param array<int, string> $languages
     *
     * @return array<string, SlugEntry>
     */
    private function loadEntries(string $apiKey, array $languages): array
    {
        $cacheKey = self::cacheKey($apiKey);
        $version = $this->readVersion();
        $entries = $this->readCache($cacheKey);

        if ([] === $this->outdatedLanguages($entries, $languages, $version, time())) {
            return $entries;
        }

        // A language with no entry has nothing to serve meanwhile: wait for the request refreshing
        // it rather than 404 every translated slug and render untranslated links.
        $isCold = [] !== array_diff($languages, array_keys($entries));
        $mutex = \Craft::$app->getMutex();
        if (!$mutex->acquire($cacheKey, $isCold ? self::FETCH_BUDGET : 0)) {
            // A cold request waited: the holder may have written the cache meanwhile.
            return $isCold ? $this->readCache($cacheKey) : $entries;
        }

        try {
            // Read again under the lock: the request that held it may have just refreshed.
            $entries = $this->readCache($cacheKey);
            $now = time();
            $outdated = $this->outdatedLanguages($entries, $languages, $version, $now);
            if ([] !== $outdated) {
                $entries = $this->refresh($entries, $outdated, $version, $apiKey, $now);
                \Craft::$app->getCache()->set($cacheKey, $entries, 0);
            }
        } finally {
            $mutex->release($cacheKey);
        }

        return $entries;
    }

    /**
     * @return array<string, SlugEntry>
     */
    private function readCache(string $cacheKey): array
    {
        $entries = \Craft::$app->getCache()->get($cacheKey);

        /* @var array<string, SlugEntry> $entries */
        return \is_array($entries) ? $entries : [];
    }

    /**
     * Weglot 2 names the field in the plural; the singular versions.slugTranslation belongs to the
     * Weglot 1 CDN settings.
     */
    private function readVersion(): ?string
    {
        $versions = Plugin::getInstance()->getOption()->getOption('versions');
        $version = \is_array($versions) ? ($versions['slugTranslations'] ?? null) : null;

        return \is_int($version) || \is_string($version) ? (string) $version : null;
    }

    /**
     * Per language, never cache-wide: a language that keeps failing must not make the others be
     * refetched on every request.
     *
     * @param array<string, SlugEntry> $entries
     * @param array<int, string>       $languages
     *
     * @return array<int, string>
     */
    private function outdatedLanguages(array $entries, array $languages, ?string $version, int $now): array
    {
        $outdated = [];
        foreach ($languages as $language) {
            $entry = $entries[$language] ?? null;
            if (null === $entry) {
                $outdated[] = $language;
                continue;
            }

            if ($entry['retry_after'] > $now) {
                continue;
            }

            if ($entry['version'] !== $version || $now - $entry['fetched_at'] > self::MAX_AGE) {
                $outdated[] = $language;
            }
        }

        return $outdated;
    }

    /**
     * @param array<string, SlugEntry> $entries
     * @param array<int, string>       $languages
     *
     * @return array<string, SlugEntry>
     */
    private function refresh(array $entries, array $languages, ?string $version, string $apiKey, int $now): array
    {
        $apiBaseUrl = Plugin::getInstance()->getOption()->getOption('api_base_url');
        $host = \is_string($apiBaseUrl) && '' !== $apiBaseUrl ? rtrim($apiBaseUrl, '/') : HelperApi::getApiUrlV2();

        $started = microtime(true);
        foreach ($languages as $index => $language) {
            $remaining = self::FETCH_BUDGET - (microtime(true) - $started);
            if ($remaining <= 0) {
                break;
            }

            try {
                $fetched = $this->fetch($host, $apiKey, $language, min(self::REQUEST_TIMEOUT, $remaining));
            } catch (GuzzleException $e) {
                \Craft::warning('Weglot 2 slugs could not be fetched, API unreachable: '.$e->getMessage(), __METHOD__);

                // The host is down or too slow: the next languages would only spend the rest of the
                // budget on the page render, and again on every request until they are postponed.
                foreach (\array_slice($languages, $index) as $pending) {
                    $entries[$pending] = $this->postpone($entries[$pending] ?? null, $now);
                }
                break;
            }

            if (null === $fetched) {
                $entries[$language] = $this->postpone($entries[$language] ?? null, $now);
                continue;
            }

            $entries[$language] = [
                'version' => $version,
                'fetched_at' => $now,
                'retry_after' => 0,
                'slugs' => $fetched['slugs'],
                'redirects' => $fetched['redirects'],
            ];
        }

        return $entries;
    }

    /**
     * Keeps the previous slugs, if any, until the next attempt.
     *
     * @param SlugEntry|null $entry
     *
     * @return SlugEntry
     */
    private function postpone(?array $entry, int $now): array
    {
        $entry ??= ['version' => null, 'fetched_at' => 0, 'retry_after' => 0, 'slugs' => [], 'redirects' => []];
        $entry['retry_after'] = $now + self::RETRY_DELAY;

        return $entry;
    }

    /**
     * @return array{slugs: array<string, string>, redirects: array<string, string>}|null null when this language failed
     *
     * @throws GuzzleException when the API could not be reached at all
     */
    private function fetch(string $host, string $apiKey, string $language, float $timeout): ?array
    {
        try {
            $response = $this->createHttpClient()->request('GET', $host.self::ROUTE, [
                'query' => ['language' => $language],
                'headers' => ['Authorization' => 'Key '.$apiKey],
                'timeout' => $timeout,
                'http_errors' => false,
            ]);
        } catch (RequestException $e) {
            \Craft::warning(\sprintf('Weglot 2 slugs for "%s" could not be fetched: %s', $language, $e->getMessage()), __METHOD__);

            return null;
        }

        $body = json_decode((string) $response->getBody(), true);
        if (200 !== $response->getStatusCode() || !\is_array($body)) {
            \Craft::warning(\sprintf('Weglot 2 slugs for "%s" could not be fetched: HTTP %d', $language, $response->getStatusCode()), __METHOD__);

            return null;
        }

        return [
            'slugs' => $this->sanitizeMap($body['slugs'] ?? []),
            'redirects' => $this->sanitizeMap($body['redirects'] ?? []),
        ];
    }

    /**
     * Keeps the pairs that can stand for one path segment.
     *
     * @return array<string, string>
     */
    private function sanitizeMap(mixed $map): array
    {
        if (!\is_array($map)) {
            return [];
        }

        $clean = [];
        foreach ($map as $from => $to) {
            $from = (string) $from;
            if (!\is_string($to) || '' === $from || '' === $to || $from === $to || str_contains($from.$to, '/')) {
                continue;
            }
            $clean[$from] = $to;
        }

        return $clean;
    }

    /**
     * @param SlugEntry $entry
     *
     * @return SlugMaps
     */
    private function buildMaps(array $entry): array
    {
        $slugs = $entry['slugs'];
        $counts = array_count_values($slugs);

        $reverse = [];
        $forward = [];
        foreach ($slugs as $original => $translated) {
            // A translated slug shared by two pages, or equal to another page's original slug,
            // cannot be resolved to one page.
            if ($counts[$translated] > 1 || isset($slugs[$translated])) {
                continue;
            }
            $reverse[$translated] = (string) $original;
            $forward[(string) $original] = $translated;
        }

        $redirects = [];
        foreach ($entry['redirects'] as $former => $current) {
            // The current slug wins: a former slug another page now uses belongs to that page, and a
            // target that is no longer current is dropped, which also rules out loops.
            if (isset($counts[$former]) || !isset($reverse[$current])) {
                continue;
            }
            $redirects[(string) $former] = $current;
        }

        return ['forward' => $forward, 'reverse' => $reverse, 'redirects' => $redirects];
    }
}
