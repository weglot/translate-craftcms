<?php

namespace Weglot\Vendor\Weglot\Client;

use Weglot\Vendor\Psr\Cache\CacheItemPoolInterface;
use Weglot\Vendor\Weglot\Client\Api\Exception\ApiError;
use Weglot\Vendor\Weglot\Client\Caching\Cache;
use Weglot\Vendor\Weglot\Client\Caching\CacheInterface;
use Weglot\Vendor\Weglot\Client\HttpClient\ClientInterface;
use Weglot\Vendor\Weglot\Client\HttpClient\CurlClient;
class Client
{
    /**
     * Library version.
     *
     * @var string
     */
    const VERSION = '0.5.11';
    /**
     * Weglot API Key.
     *
     * @var string
     */
    protected $apiKey;
    /**
     * Weglot settings file Version.
     *
     * @var string|int
     */
    protected $version;
    /**
     * Options for client.
     *
     * @var array<string, mixed>
     */
    protected $options;
    /**
     * Http Client.
     *
     * @var ClientInterface
     */
    protected $httpClient;
    /**
     * @var Profile
     */
    protected $profile;
    /**
     * @var CacheInterface
     */
    protected $cache;
    /**
     * @param string               $apiKey            your Weglot API key
     * @param int                  $translationEngine
     * @param string|int           $version           your settings file version
     * @param array<string, mixed> $options           an array of options, currently only "host" is implemented
     */
    public function __construct($apiKey, $translationEngine, $version = '1', $options = [])
    {
        $this->apiKey = $apiKey;
        $this->version = $version;
        $this->profile = new Profile($apiKey, $translationEngine);
        $this->setHttpClient()->setOptions($options)->setCache();
    }
    /**
     * Creating Guzzle HTTP connector based on $options.
     *
     * @return void
     */
    protected function setupConnector()
    {
        $this->httpClient = new CurlClient();
    }
    /**
     * Default options values.
     *
     * @return array<string, mixed>
     */
    public function defaultOptions()
    {
        return ['host' => 'https://api.weglot.com'];
    }
    /**
     * @return array<string, mixed>
     */
    public function getOptions()
    {
        return $this->options;
    }
    /**
     * @param array<string, mixed> $options
     *
     * @return $this
     */
    public function setOptions($options)
    {
        // merging default options with user options
        $this->options = array_merge($this->defaultOptions(), $options);
        return $this;
    }
    /**
     * @param ClientInterface|null $httpClient
     * @param string|null          $customHeader
     *
     * @return $this
     */
    public function setHttpClient($httpClient = null, $customHeader = null)
    {
        if (null === $httpClient) {
            $httpClient = new CurlClient();
            $header = 'Weglot-Context: PHP\\' . self::VERSION;
            if (null !== $customHeader) {
                $header .= ' ' . $customHeader;
            }
            $httpClient->addHeader($header);
        }
        if ($httpClient instanceof ClientInterface) {
            $this->httpClient = $httpClient;
        }
        return $this;
    }
    /**
     * @return ClientInterface
     */
    public function getHttpClient()
    {
        return $this->httpClient;
    }
    /**
     * @return Profile
     */
    public function getProfile()
    {
        return $this->profile;
    }
    /**
     * @param CacheInterface|null $cache
     *
     * @return $this
     */
    public function setCache($cache = null)
    {
        if (!$cache instanceof CacheInterface) {
            $cache = new Cache();
        }
        $this->cache = $cache;
        return $this;
    }
    /**
     * @return CacheInterface
     */
    public function getCache()
    {
        return $this->cache;
    }
    /**
     * @param CacheItemPoolInterface|null $cacheItemPool
     *
     * @return $this
     */
    public function setCacheItemPool($cacheItemPool)
    {
        $this->getCache()->setItemPool($cacheItemPool);
        return $this;
    }
    /**
     * Recursively converts an array or string to UTF-8 encoding.
     *
     * @param mixed $data the data to convert to UTF-8 encoding
     *
     * @return mixed the data with all string values converted to UTF-8 encoding
     */
    public function recursivelyConvertToUtf8($data)
    {
        if (\is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->recursivelyConvertToUtf8($value);
            }
        } elseif (\is_string($data)) {
            if (mb_check_encoding($data, 'UTF-8')) {
                return $data;
            }
            $encoding = mb_detect_encoding($data, mb_detect_order(), \true);
            if (\false !== $encoding) {
                return mb_convert_encoding($data, 'UTF-8', $encoding);
            }
            return $data;
        }
        return $data;
    }
    /**
     * Make the API call and return the response.
     *
     * @param string               $method   method to use for given endpoint
     * @param string               $endpoint endpoint to hit on API
     * @param array<string, mixed> $body     body content of the request as an array
     * @param bool                 $asArray  to only get the body decoded
     *
     * @return ($asArray is true ? array<mixed> : array{string, int, array<string, string>})
     *
     * @throws ApiError
     */
    public function makeRequest($method, $endpoint, $body = [], $asArray = \true)
    {
        try {
            if ('GET' === $method) {
                $urlParams = array_merge(['api_key' => $this->apiKey, 'v' => $this->version], $body);
                $body = [];
            } else {
                $urlParams = ['api_key' => $this->apiKey, 'v' => $this->version];
            }
            // Check JSON encoding validity before make API call
            $jsonBody = json_encode($body);
            if (\false === $jsonBody) {
                // Attempt to convert data to UTF-8.
                $body = $this->recursivelyConvertToUtf8($body);
                $jsonBody = json_encode($body);
                if (\false === $jsonBody) {
                    throw new \Exception('JSON encoding error: ' . json_last_error_msg());
                }
            }
            list($rawBody, $httpStatusCode, $httpHeader) = $this->getHttpClient()->request($method, $this->makeAbsUrl($endpoint), $urlParams, $body);
        } catch (\Exception $e) {
            throw new ApiError($e->getMessage(), $body);
        }
        if ($asArray) {
            return json_decode($rawBody, \true);
        }
        return [$rawBody, $httpStatusCode, $httpHeader];
    }
    /**
     * @param string $endpoint
     *
     * @return string
     */
    protected function makeAbsUrl($endpoint)
    {
        return $this->options['host'] . $endpoint;
    }
}
