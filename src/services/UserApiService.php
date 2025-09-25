<?php

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

        $client = \Craft::createGuzzleClient();
        $url = \sprintf('%s/projects/owner', HelperApi::getApiUrl());

        $requestOptions = [
            'query' => [
                'api_key' => $apiKey,
            ],
            'headers' => [
                'Accept' => 'application/json',
            ],
            'timeout' => 5,
        ];
        try {
            $response = $client->request('GET', $url, $requestOptions);
            $contents = $response->getBody()->getContents();

            $decoded = json_decode($contents, true);

            if (\JSON_ERROR_NONE !== json_last_error()) {
                return ['error' => true, 'message' => 'Invalid JSON response from API.'];
            }

            if (isset($decoded['succeeded']) && 1 !== $decoded['succeeded']) {
                $errorMessage = $decoded['error'] ?? 'Invalid API Key.';

                return ['error' => true, 'message' => $errorMessage];
            }

            return $decoded;
        } catch (RequestException $e) {
            $message = 'API request failed.';

            if ($e->hasResponse()) {
                $statusCode = $e->getResponse()->getStatusCode();
                $reason = $e->getResponse()->getReasonPhrase();
                $message = \sprintf('API request failed with status %d: %s', $statusCode, $reason);
            }

            return ['error' => true, 'message' => $message];
        }
    }
}
