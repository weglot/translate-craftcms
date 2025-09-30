<?php

namespace Weglot\Vendor\Weglot\Client\Endpoint;

use Weglot\Vendor\Weglot\Client\Api\Exception\ApiError;
use Weglot\Vendor\Weglot\Client\Caching\CacheInterface;
use Weglot\Vendor\Weglot\Client\Client;

abstract class Endpoint
{
    public const METHOD = 'GET';
    public const ENDPOINT = '/';
    /**
     * @var Client
     */
    protected $client;

    public function __construct(Client $client)
    {
        $this->setClient($client);
    }

    /**
     * @return void
     */
    public function setClient(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @return CacheInterface
     */
    public function getCache()
    {
        return $this->getClient()->getCache();
    }

    /**
     * @return string
     */
    public function getPath()
    {
        $parentClass = static::class;

        return $parentClass::ENDPOINT;
    }

    /**
     * Used to run endpoint onto given Client.
     *
     * @return mixed
     */
    abstract public function handle();

    /**
     * @param array<mixed> $body
     * @param bool         $asArray
     *
     * @return ($asArray is true ? array<mixed> : array{string, int, array<string, string>})
     *
     * @throws ApiError
     */
    protected function request(array $body = [], $asArray = \true)
    {
        $parentClass = static::class;
        $response = $this->getClient()->makeRequest($parentClass::METHOD, $parentClass::ENDPOINT, $body, $asArray);

        return $response;
    }
}
