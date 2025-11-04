<?php

namespace Weglot\Vendor\Weglot\Client\Endpoint;

use Weglot\Vendor\Weglot\Client\Api\Exception\ApiError;
use Weglot\Vendor\Weglot\Client\Api\Exception\InputAndOutputCountMatchException;
use Weglot\Vendor\Weglot\Client\Api\Exception\InvalidWordTypeException;
use Weglot\Vendor\Weglot\Client\Api\Exception\MissingRequiredParamException;
use Weglot\Vendor\Weglot\Client\Api\Exception\MissingWordsOutputException;
use Weglot\Vendor\Weglot\Client\Api\TranslateEntry;
use Weglot\Vendor\Weglot\Client\Client;
use Weglot\Vendor\Weglot\Client\Factory\Translate as TranslateFactory;
class CdnTranslate extends Endpoint
{
    const METHOD = 'POST';
    const ENDPOINT = '/translate';
    const WORDS_LIMIT = 600;
    /**
     * @var TranslateEntry
     */
    protected $translateEntry;
    public function __construct(TranslateEntry $translateEntry, Client $client)
    {
        $this->setTranslateEntry($translateEntry);
        $currentHost = $client->getOptions()['host'];
        if ($currentHost) {
            $cdnHost = str_replace('https://api.weglot.', 'https://cdn-api-weglot.', $currentHost);
            $client->setOptions(['host' => $cdnHost]);
        }
        parent::__construct($client);
    }
    /**
     * @return TranslateEntry
     */
    public function getTranslateEntry()
    {
        return $this->translateEntry;
    }
    /**
     * @return $this
     */
    public function setTranslateEntry(TranslateEntry $translateEntry)
    {
        $this->translateEntry = $translateEntry;
        return $this;
    }
    /**
     * @return TranslateEntry
     *
     * @throws ApiError
     * @throws InputAndOutputCountMatchException
     * @throws InvalidWordTypeException
     * @throws MissingRequiredParamException
     * @throws MissingWordsOutputException
     */
    public function handle()
    {
        $asArray = $this->translateEntry->jsonSerialize();
        if (empty($asArray['words'])) {
            throw new ApiError('Empty words passed', $asArray);
        }
        $wordChunks = array_chunk($asArray['words'], self::WORDS_LIMIT);
        $response = [];
        foreach ($wordChunks as $chunk) {
            $payload = $asArray;
            $payload['words'] = $chunk;
            list($rawBody, $httpStatusCode) = $this->request($payload, \false);
            if (200 === $httpStatusCode) {
                $chunkResponse = json_decode($rawBody, \true);
                foreach (['from_words', 'to_words', 'ids'] as $key) {
                    $response[$key] = array_merge(isset($response[$key]) ? $response[$key] : [], isset($chunkResponse[$key]) ? $chunkResponse[$key] : []);
                }
            } else {
                $originalWords = array_column($chunk, 'w');
                foreach (['from_words', 'to_words'] as $key) {
                    $response[$key] = array_merge($response[$key] ?? [], $originalWords);
                }
            }
        }
        if (empty($response)) {
            throw new ApiError('All API calls failed', $asArray);
        }
        $factory = new TranslateFactory($response);
        return $factory->handle();
    }
}
