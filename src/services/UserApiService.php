<?php

declare(strict_types=1);

namespace weglot\craftweglot\services;

use craft\base\Component;
use GuzzleHttp\Exception\RequestException;
use weglot\craftweglot\helpers\HelperApi;
use weglot\craftweglot\Plugin;

class UserApiService extends Component
{
    private const WORKSPACE_CACHE_PREFIX = 'weglot_workspace_slug';

    private const WORKSPACE_CACHE_TTL = 3600;

    /**
     * A failed lookup yields an empty slug, which blanks every dashboard link. Keep it
     * just long enough to throttle retries, not long enough for a network blip to cost
     * a whole hour of broken links.
     */
    private const WORKSPACE_FAILURE_TTL = 300;

    private ?string $memoizedApiKey = null;

    private string $memoizedWorkspaceSlug = '';

    /**
     * Scoped to the API key: saving the settings refreshes the option caches but never
     * clears this entry, so a shared key would keep serving the previous project's
     * workspace for a whole TTL after the API key changes.
     */
    public static function workspaceCacheKey(string $apiKey): string
    {
        return self::WORKSPACE_CACHE_PREFIX.'_'.substr(sha1($apiKey), 0, 16);
    }

    /**
     * @return array<string, mixed>
     */
    public function getUserInfo(string $apiKey): array
    {
        if ('' === $apiKey || '0' === $apiKey) {
            return ['error' => true, 'message' => 'API key cannot be empty.'];
        }

        $url = \sprintf('%s/project-settings', HelperApi::getApiUrl());

        $requestOptions = [
            'query' => ['api_key' => $apiKey],
            'headers' => ['Accept' => 'application/json'],
            'timeout' => 5,
        ];

        try {
            $response = \Craft::createGuzzleClient()->request('GET', $url, $requestOptions);

            return $this->decodeResponse($response->getBody()->getContents());
        } catch (RequestException $e) {
            return $this->requestError($e);
        }
    }

    /**
     * V2 projects carry no `organization_slug`; the dashboard URL is built from the
     * workspace slug instead, which only this endpoint exposes.
     */
    public function getWorkspaceSlug(string $apiKey): string
    {
        if (!HelperApi::isV2ApiKey($apiKey)) {
            return '';
        }

        // DashboardHelper asks once per quick link, so without this a failing lookup
        // would repeat its HTTP timeout for every card on the page.
        if ($this->memoizedApiKey === $apiKey) {
            return $this->memoizedWorkspaceSlug;
        }

        $cache = \Craft::$app->getCache();
        $cacheKey = self::workspaceCacheKey($apiKey);
        $cached = $cache->get($cacheKey);

        if (\is_string($cached)) {
            return $this->memoize($apiKey, $cached);
        }

        $slug = $this->fetchWorkspaceSlug($apiKey);
        $cache->set(
            $cacheKey,
            $slug,
            '' === $slug ? self::WORKSPACE_FAILURE_TTL : self::WORKSPACE_CACHE_TTL
        );

        return $this->memoize($apiKey, $slug);
    }

    private function memoize(string $apiKey, string $slug): string
    {
        $this->memoizedApiKey = $apiKey;
        $this->memoizedWorkspaceSlug = $slug;

        return $slug;
    }

    protected function fetchWorkspaceSlug(string $apiKey): string
    {
        $apiBaseUrl = Plugin::getInstance()->getOption()->getOption('api_base_url');
        $host = \is_string($apiBaseUrl) && '' !== $apiBaseUrl ? $apiBaseUrl : HelperApi::getApiUrlV2();

        $requestOptions = [
            'headers' => [
                'Accept' => 'application/json',
                'Content-type' => 'application/json',
                'Authorization' => 'Key '.$apiKey,
            ],
            'timeout' => 5,
        ];

        try {
            $response = \Craft::createGuzzleClient()->request('GET', $host.'/workspaces/current', $requestOptions);
            $decoded = json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            \Craft::error('Error retrieving Weglot workspace info: '.$e->getMessage(), __METHOD__);

            return '';
        }

        if (!\is_array($decoded) || !\is_string($decoded['slug'] ?? null)) {
            return '';
        }

        return trim($decoded['slug']);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeResponse(string $contents): array
    {
        $decoded = json_decode($contents, true);

        if (\JSON_ERROR_NONE !== json_last_error() || !\is_array($decoded)) {
            return ['error' => true, 'message' => 'Invalid JSON response from API.'];
        }

        if (isset($decoded['succeeded']) && 1 !== $decoded['succeeded']) {
            return ['error' => true, 'message' => $decoded['error'] ?? 'Invalid API Key.'];
        }

        return $decoded;
    }

    /**
     * @return array<string, mixed>
     */
    private function requestError(RequestException $e): array
    {
        if ($e->hasResponse()) {
            $statusCode = $e->getResponse()->getStatusCode();
            $reason = $e->getResponse()->getReasonPhrase();

            return ['error' => true, 'message' => \sprintf('API request failed with status %d: %s', $statusCode, $reason)];
        }

        return ['error' => true, 'message' => 'API request failed.'];
    }
}
