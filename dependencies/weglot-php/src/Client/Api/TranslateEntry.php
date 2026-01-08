<?php

namespace Weglot\Client\Api;

use Weglot\Client\Api\Exception\MissingRequiredParamException;

if (!\function_exists('array_keys_exists')) {
    /**
     * Used to check if multiple keys are defined in given array.
     *
     * @param array<string|int> $keys
     * @param array<mixed>      $arr
     *
     * @return bool
     */
    function array_keys_exists(array $keys, array $arr)
    {
        return !array_diff_key(array_flip($keys), $arr);
    }
}

class TranslateEntry implements \JsonSerializable
{
    /**
     * @var array<string, mixed>
     */
    protected $params;

    /**
     * @var WordCollection
     */
    protected $inputWords;

    /**
     * @var WordCollection
     */
    protected $outputWords;

    /**
     * @param array<string, mixed> $params Params of the translate entry, required fields: language_from, language_to, bot, request_url & optional: title ("Empty title" by default)
     * @param WordCollection|null  $words  Collection of words
     *
     * @throws MissingRequiredParamException If params are missing we throw this exception
     */
    public function __construct(array $params, ?WordCollection $words = null)
    {
        $this->setParams($params)
            ->setInputWords($words)
            ->setOutputWords();
    }

    /**
     * Default params values.
     *
     * @return array<string, mixed>
     */
    protected function defaultParams()
    {
        return [
            'title' => 'Empty title',
        ];
    }

    /**
     * Required params field names.
     *
     * @return array<string>
     */
    protected function requiredParams()
    {
        return [
            'language_from',
            'language_to',
            'bot',
            'request_url',
        ];
    }

    /**
     * @param string|null $key
     *
     * @return mixed
     */
    public function getParams($key = null)
    {
        if (null !== $key) {
            if (isset($this->params[$key])) {
                return $this->params[$key];
            }

            return false;
        }

        return $this->params;
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return $this
     *
     * @throws MissingRequiredParamException If params are missing we throw this exception
     */
    public function setParams(array $params)
    {
        // merging default params with user params
        $this->params = array_merge($this->defaultParams(), $params);

        if (!array_keys_exists($this->requiredParams(), $this->params)) {
            throw new MissingRequiredParamException();
        }

        return $this;
    }

    /**
     * @return WordCollection
     */
    public function getInputWords()
    {
        return $this->inputWords;
    }

    /**
     * Used to fill input words collection
     * If $words is null, it would put an empty word collection.
     *
     * @param WordCollection|null $words
     *
     * @return $this
     */
    public function setInputWords($words = null)
    {
        if (null === $words) {
            $this->inputWords = new WordCollection();
        } else {
            $this->inputWords = $words;
        }

        return $this;
    }

    /**
     * @return WordCollection
     */
    public function getOutputWords()
    {
        return $this->outputWords;
    }

    /**
     * Used to fill output words collection
     * If $words is null, it would put an empty word collection.
     *
     * @param WordCollection|null $words
     *
     * @return $this
     */
    public function setOutputWords($words = null)
    {
        if (null === $words) {
            $this->outputWords = new WordCollection();
        } else {
            $this->outputWords = $words;
        }

        return $this;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'l_from' => $this->params['language_from'],
            'l_to' => $this->params['language_to'],
            'bot' => $this->params['bot'],
            'title' => $this->params['title'],
            'request_url' => $this->params['request_url'],
            'words' => $this->inputWords->jsonSerialize(),
        ];
    }
}
