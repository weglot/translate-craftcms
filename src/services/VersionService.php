<?php

declare(strict_types=1);

namespace weglot\craftweglot\services;

use craft\base\Component;
use GuzzleHttp\Exception\GuzzleException;
use weglot\craftweglot\helpers\HelperApi;

class VersionService extends Component
{
    private const ROUTING_FILE = '/wp-routing.json';
    private const CACHE_KEY = 'weglot_v2_percentage_split';
    private const CACHE_TTL = 3600;

    /**
     * Share of new installs that should be sent to the V2 onboarding, driven
     * remotely by the routing file on the CDN.
     */
    public function getV2PercentageSplit(): int
    {
        $cache = \Craft::$app->getCache();
        $cached = $cache->get(self::CACHE_KEY);

        if (\is_int($cached)) {
            return $cached;
        }

        $percentage = $this->fetchV2PercentageSplit();
        $cache->set(self::CACHE_KEY, $percentage, self::CACHE_TTL);

        return $percentage;
    }

    public function getRandomOnboardingVersion(): int
    {
        $percentage = $this->getV2PercentageSplit();

        if ($percentage <= 0) {
            return HelperApi::VERSION_V1;
        }

        return random_int(1, 100) <= $percentage
            ? HelperApi::VERSION_V2
            : HelperApi::VERSION_V1;
    }

    private function fetchV2PercentageSplit(): int
    {
        $url = HelperApi::getProductionRootCdnBase().self::ROUTING_FILE;

        try {
            $response = \Craft::createGuzzleClient()->request('GET', $url, ['timeout' => 3]);

            if (200 !== $response->getStatusCode()) {
                return 0;
            }

            $data = json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException) {
            return 0;
        }

        if (!\is_array($data) || !isset($data['v2percentage']) || !is_numeric($data['v2percentage'])) {
            return 0;
        }

        return (int) $data['v2percentage'];
    }
}
