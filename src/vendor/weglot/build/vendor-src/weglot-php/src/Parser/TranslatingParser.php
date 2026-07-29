<?php

namespace Weglot\Vendor\Weglot\Parser;

use Weglot\Vendor\Weglot\Client\Api\Exception\ApiError;
use Weglot\Vendor\Weglot\Client\Api\Exception\InputAndOutputCountMatchException;
use Weglot\Vendor\Weglot\Client\Api\Exception\MissingWordsOutputException;
use Weglot\Vendor\Weglot\Client\Client;
use Weglot\Vendor\Weglot\Client\Endpoint\CdnTranslate;
use Weglot\Vendor\Weglot\Parser\ConfigProvider\ConfigProviderInterface;
use Weglot\Vendor\Weglot\Parser\ConfigProvider\ServerConfigProvider;
use Weglot\Vendor\Weglot\Parser\Definitions\Exception\InvalidWordTypeException;
use Weglot\Vendor\Weglot\Parser\Definitions\Exception\MissingRequiredParamException;
use Weglot\Vendor\Weglot\Parser\Definitions\TranslateEntry;
use Weglot\Vendor\Weglot\Parser\Util\SourceType;
use Weglot\Vendor\WGSimpleHtmlDom\simple_html_dom;
/**
 * Adds the Weglot API communication layer on top of the standalone parser.
 *
 * The pure parsing/checking/formatting logic lives in the
 * weglot/weglot-parser-php package (Weglot\Parser\Parser). This class is the
 * only place where Weglot API calls happen.
 */
class TranslatingParser extends Parser
{
    /**
     * @var Client
     */
    protected $client;
    /**
     * @param array<string>                    $excludeBlocks
     * @param array<int, array<string, mixed>> $customSwitchers
     * @param array<string>                    $whiteList
     * @param array<string>                    $translateInsideExclusionsBlocks
     */
    public function __construct(Client $client, ConfigProviderInterface $config, array $excludeBlocks = [], array $customSwitchers = [], array $whiteList = [], array $translateInsideExclusionsBlocks = [])
    {
        parent::__construct($client->getProfile()->getTranslationEngine(), $config, $excludeBlocks, $customSwitchers, $whiteList, $translateInsideExclusionsBlocks);
        $this->setClient($client);
    }
    /**
     * @return $this
     */
    public function setClient(Client $client)
    {
        $this->client = $client;
        return $this;
    }
    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }
    /**
     * @param string             $source
     * @param string             $languageFrom
     * @param string             $languageTo
     * @param array<int, string> $extraKeys
     * @param string             $canonical
     * @param string             $requestUrl
     *
     * @return string
     *
     * @throws ApiError
     * @throws InputAndOutputCountMatchException
     * @throws InvalidWordTypeException
     * @throws MissingRequiredParamException
     * @throws MissingWordsOutputException
     */
    public function translate($source, $languageFrom, $languageTo, $extraKeys = [], $canonical = '', $requestUrl = '')
    {
        // setters
        $this->setLanguageFrom($languageFrom)->setLanguageTo($languageTo);
        $results = $this->parse($source, $extraKeys);
        $tree = $results['tree'];
        if (SourceType::SOURCE_HTML === $tree['type']) {
            $title = $this->getTitle($tree['dom']);
        } else {
            $title = '';
        }
        // api communication
        if (0 === \count($this->getWords())) {
            return $source;
        }
        $translated = $this->apiTranslate($title, $canonical, $requestUrl);
        return $this->formatters($source, $translated, $tree);
    }
    /**
     * @param string $title
     * @param string $canonical
     * @param string $requestUrl
     *
     * @return TranslateEntry
     *
     * @throws ApiError
     * @throws InputAndOutputCountMatchException
     * @throws InvalidWordTypeException
     * @throws MissingRequiredParamException
     * @throws MissingWordsOutputException
     */
    protected function apiTranslate($title = null, $canonical = '', $requestUrl = '')
    {
        // Translate endpoint parameters
        $params = ['language_from' => $this->getLanguageFrom(), 'language_to' => $this->getLanguageTo()];
        // if data is coming from $_SERVER, load it ...
        if ($this->getConfigProvider() instanceof ServerConfigProvider) {
            $this->getConfigProvider()->loadFromServer($canonical);
        }
        if ($this->getConfigProvider()->getAutoDiscoverTitle()) {
            $params['title'] = $title;
        }
        $params = array_merge($params, $this->getConfigProvider()->asArray());
        // if not empty we use this request_url (from WordPress)
        if (!empty($requestUrl)) {
            $params['request_url'] = $requestUrl;
        }
        try {
            $translate = new TranslateEntry($params);
            $translate->setInputWords($this->getWords());
        } catch (\Exception $e) {
            exit($e->getMessage());
        }
        $translate = new CdnTranslate($translate, $this->client);
        return $translate->handle();
    }
    /**
     * @return string
     */
    protected function getTitle(simple_html_dom $dom)
    {
        $title = 'Empty title';
        foreach ($dom->find('title') as $node) {
            if ('' != $node->innertext) {
                $title = $node->innertext;
            }
        }
        return $title;
    }
}
