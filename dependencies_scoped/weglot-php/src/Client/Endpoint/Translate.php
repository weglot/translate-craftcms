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

class Translate extends Endpoint
{
    public const METHOD = 'POST';
    public const ENDPOINT = '/translate';
    /**
     * @var TranslateEntry
     */
    protected $translateEntry;

    public function __construct(TranslateEntry $translateEntry, Client $client)
    {
        $this->setTranslateEntry($translateEntry);
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
     * @return array
     */
    protected function beforeRequest()
    {
        // init
        $words = $this->getTranslateEntry()->getInputWords()->jsonSerialize();
        $requestWords = $cachedWords = $fullWords = [];
        $defaultParams = ['from' => $this->getTranslateEntry()->getParams('language_from'), 'to' => $this->getTranslateEntry()->getParams('language_to')];
        // fetch words to check if anything hit the cache
        foreach ($words as $key => $word) {
            // adding from & to languages to make key unique by language-pair
            $word = array_merge($word, $defaultParams);
            $cachedWord = $this->getCache()->getWithGenerate($word);
            // default behavior > sending word to request
            $where = 'request';
            $element = $word;
            $array = &$requestWords;
            // cached behavior > word is present in cache !
            if ($cachedWord->isHit()) {
                $where = 'cached';
                $element = $cachedWord->get();
                $array = &$cachedWords;
            }
            // get next element place
            $next = \count($array);
            // apply choosed behavior
            $array[$next] = $element;
            $fullWords[$key] = ['where' => $where, 'place' => $next];
        }

        return [$requestWords, $cachedWords, $fullWords];
    }

    /**
     * @return array
     */
    protected function afterRequest(array $response, array $beforeRequestResult)
    {
        // init
        [$requestWords, $cachedWords, $fullWords] = $beforeRequestResult;
        $fromWords = $toWords = [];
        $defaultParams = ['from' => $this->getTranslateEntry()->getParams('language_from'), 'to' => $this->getTranslateEntry()->getParams('language_to')];
        // fetch all words in one array
        foreach ($fullWords as $key => $details) {
            // if current word was in cache, just retrieve it
            if ('cached' === $details['where']) {
                $fromWords[$key] = $cachedWords[$details['place']]['from'];
                $toWords[$key] = $cachedWords[$details['place']]['to'];
                continue;
            }
            // word was requested, let's retrieve data from response
            $word = $requestWords[$details['place']];
            $from = $response['from_words'][$details['place']];
            $to = $response['to_words'][$details['place']];
            // caching requested word
            $word = array_merge($word, $defaultParams);
            $cachedWord = $this->getCache()->getWithGenerate($word);
            $cachedWord->set(['from' => $from, 'to' => $to]);
            $this->getCache()->save($cachedWord);
            // then re-inject word inside
            $fromWords[$key] = $from;
            $toWords[$key] = $to;
        }
        $response['from_words'] = $fromWords;
        $response['to_words'] = $toWords;

        return $response;
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
        $beforeRequest = [];
        $asArray = $this->translateEntry->jsonSerialize();
        $response = [];
        if ($this->getCache()->enabled()) {
            $beforeRequest = $this->beforeRequest();
            $asArray['words'] = $beforeRequest[0];
        }
        if (empty($asArray['words'])) {
            if ($this->getCache()->enabled()) {
                $response = $this->afterRequest($asArray, $beforeRequest);
            }
        } else {
            [$rawBody, $httpStatusCode] = $this->request($asArray, \false);
            if (200 !== $httpStatusCode) {
                throw new ApiError($rawBody, $asArray);
            }
            $response = json_decode($rawBody, \true);
            if ($this->getCache()->enabled()) {
                $response = $this->afterRequest($response, $beforeRequest);
            }
        }
        $factory = new TranslateFactory($response);

        return $factory->handle();
    }
}
