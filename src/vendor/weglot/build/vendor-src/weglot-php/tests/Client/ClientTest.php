<?php

namespace Weglot\Vendor\Weglot\Tests\Client;

use Weglot\Vendor\PHPUnit\Framework\TestCase;
use Weglot\Vendor\Weglot\Client\Client;
use Weglot\Vendor\Weglot\Client\HttpClient\CurlClient;
class ClientTest extends TestCase
{
    /**
     * @var Client
     */
    protected $client;
    protected function setup(): void
    {
        $this->client = new Client($_SERVER['WG_API_KEY'], 3, 1);
        $this->client->setOptions(['host' => 'https://api.weglot.dev']);
        $headerKey = $_ENV['WEGLOT_WAF_HEADER_KEY'];
        $headerValue = $_ENV['WEGLOT_WAF_HEADER_VALUE'];
        if ($headerKey && $headerValue) {
            $this->client->getHttpClient()->addHeader("{$headerKey}: {$headerValue}");
        }
    }
    public function testOptions(): void
    {
        $options = $this->client->getOptions();
        $this->assertEquals('https://api.weglot.dev', $options['host']);
    }
    public function testConnector(): void
    {
        $httpClient = $this->client->getHttpClient();
        $this->assertInstanceOf(CurlClient::class, $httpClient);
        $curlVersion = curl_version();
        $this->assertNotFalse($curlVersion);
        $headerKey = $_ENV['WEGLOT_WAF_HEADER_KEY'];
        $headerValue = $_ENV['WEGLOT_WAF_HEADER_VALUE'];
        $headers = ['Weglot-Context: PHP\\' . Client::VERSION, "{$headerKey}: {$headerValue}"];
        $this->assertEquals($headers, $httpClient->getDefaultHeaders());
    }
    public function testProfile(): void
    {
        $wgApiKeys = ['wg_bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb' => 2];
        foreach ($wgApiKeys as $wgApiKey => $version) {
            $client = new Client($wgApiKey, 3, 1);
            $profile = $client->getProfile();
            $this->assertEquals($version, $profile->getApiVersion());
            $this->assertEquals(3, $profile->getTranslationEngine());
        }
    }
    public function testMakeRequest(): void
    {
        $response = $this->client->makeRequest('GET', '/public/status', []);
        $this->assertEquals([], $response);
    }
    public function testMakeRequestAsResponse(): void
    {
        list($rawBody, $httpStatusCode, $httpHeader) = $this->client->makeRequest('GET', '/public/status', [], \false);
        $this->assertSame(200, $httpStatusCode);
    }
    public function testRecursivelyConvertToUtf8FixesJsonEncoding(): void
    {
        $badStr = "\xc3(";
        $data = ['invalid' => $badStr];
        json_encode($data);
        $this->assertEquals(\JSON_ERROR_UTF8, json_last_error(), 'JSON encoding should fail with invalid UTF-8 data');
        $converted = $this->client->recursivelyConvertToUtf8($data);
        $this->assertFalse(mb_check_encoding($converted['invalid'], 'UTF-8'), 'The string should still be invalid UTF-8 as the function does not handle this case.');
        json_encode($converted);
        $this->assertEquals(\JSON_ERROR_UTF8, json_last_error(), 'JSON encoding should still fail after the conversion attempt.');
    }
}
