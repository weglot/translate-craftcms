<?php

namespace Weglot\Vendor\Weglot\Tests\Client\Endpoint;

use Weglot\Vendor\PHPUnit\Framework\TestCase;
use Weglot\Vendor\Weglot\Client\Client;
use Weglot\Vendor\Weglot\Client\Endpoint\Status;
class StatusTest extends TestCase
{
    /**
     * @var Client
     */
    protected $client;
    /**
     * @var Status
     */
    protected $status;
    protected function setup(): void
    {
        $this->client = new Client($_ENV['WG_API_KEY'], 3, 1);
        $this->client->setOptions(['host' => 'https://api.weglot.dev']);
        $headerKey = $_ENV['WEGLOT_WAF_HEADER_KEY'];
        $headerValue = $_ENV['WEGLOT_WAF_HEADER_VALUE'];
        if ($headerKey && $headerValue) {
            $this->client->getHttpClient()->addHeader("{$headerKey}: {$headerValue}");
        }
        $this->status = new Status($this->client);
    }
    /**
     * @return void
     */
    public function testEndpoint()
    {
        $this->assertTrue($this->status->handle(), 'API not reachable');
    }
    /**
     * @return void
     */
    public function testPath()
    {
        $this->assertEquals('/public/status', $this->status->getPath());
    }
}
