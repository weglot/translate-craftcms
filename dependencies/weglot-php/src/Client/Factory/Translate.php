<?php

namespace Weglot\Client\Factory;

use Weglot\Client\Api\Exception\InputAndOutputCountMatchException;
use Weglot\Client\Api\Exception\InvalidWordTypeException;
use Weglot\Client\Api\Exception\MissingRequiredParamException;
use Weglot\Client\Api\Exception\MissingWordsOutputException;
use Weglot\Client\Api\TranslateEntry;
use Weglot\Client\Api\WordEntry;

class Translate
{
    /**
     * @var array
     */
    protected $response = [];

    public function __construct(array $response)
    {
        $this->setResponse($response);
    }

    /**
     * @return $this
     */
    public function setResponse(array $response)
    {
        $this->response = $response;

        return $this;
    }

    /**
     * @return array
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @return TranslateEntry
     *
     * @throws InputAndOutputCountMatchException
     * @throws InvalidWordTypeException
     * @throws MissingRequiredParamException
     * @throws MissingWordsOutputException
     */
    public function handle()
    {
        $response = $this->getResponse();

        $params = [
            'language_from' => $response['l_from'] ?? null,
            'language_to' => $response['l_to'] ?? null,
            'bot' => $response['bot'] ?? null,
            'request_url' => $response['request_url'] ?? null,
            'title' => $response['title'] ?? null,
        ];
        $translate = new TranslateEntry($params);

        if (!isset($response['to_words'])) {
            throw new MissingWordsOutputException();
        }

        if (\count($response['from_words']) !== \count($response['to_words'])) {
            throw new InputAndOutputCountMatchException($response);
        }

        foreach ($response['from_words'] as $fromWord) {
            $translate->getInputWords()->addOne(new WordEntry($fromWord));
        }
        foreach ($response['to_words'] as $toWord) {
            $translate->getOutputWords()->addOne(new WordEntry($toWord));
        }

        return $translate;
    }
}
