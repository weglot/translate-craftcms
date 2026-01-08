<?php

namespace Weglot\Client\HttpClient;

interface ClientInterface
{
    /**
     * @param string $service
     * @param string $value
     *
     * @return void
     */
    public function addUserAgentInfo($service, $value);

    /**
     * @return array<string, string>
     */
    public function getUserAgentInfo();

    /**
     * @param string $header
     *
     * @return void
     */
    public function addHeader($header);

    /**
     * @return array<string>
     */
    public function getDefaultHeaders();

    /**
     * @param string               $method The HTTP method being used
     * @param string               $absUrl The URL being requested, including domain and protocol
     * @param array<string, mixed> $params KV pairs for parameters
     * @param array<mixed>         $body   JSON body content (as array)
     *
     * @return array{string, int, array<string, string>}
     *
     * @throws \Exception
     */
    public function request($method, $absUrl, $params = [], $body = []);
}
