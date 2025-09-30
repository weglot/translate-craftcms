<?php

namespace Weglot\Vendor\Weglot\Client\Endpoint;

use Weglot\Vendor\Weglot\Client\Api\Exception\ApiError;
class Status extends Endpoint
{
    const METHOD = 'GET';
    const ENDPOINT = '/public/status';
    /**
     * @return bool
     *
     * @throws ApiError
     */
    public function handle()
    {
        list($rawBody, $httpStatusCode, $httpHeader) = $this->request([], \false);
        if (200 === $httpStatusCode) {
            return \true;
        }
        return \false;
    }
}
