<?php

declare(strict_types=1);

namespace Weglot\Client\Endpoint;

use Weglot\Client\Api\Exception\ApiError;

class Status extends Endpoint
{
    public const METHOD = 'GET';
    public const ENDPOINT = '/public/status';

    /**
     * @return bool
     *
     * @throws ApiError
     */
    public function handle()
    {
        [$rawBody, $httpStatusCode, $httpHeader] = $this->request([], false);

        if (200 === $httpStatusCode) {
            return true;
        }

        return false;
    }
}
