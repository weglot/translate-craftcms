<?php

declare(strict_types=1);

namespace weglot\craftweglot\services;

use craft\base\Component;
use GuzzleHttp\Exception\RequestException;
use weglot\craftweglot\helpers\HelperApi;

class UserApiService extends Component
{
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
