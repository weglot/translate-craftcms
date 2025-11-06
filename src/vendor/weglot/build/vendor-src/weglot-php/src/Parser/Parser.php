<?php

namespace Weglot\Vendor\Weglot\Parser;

use Weglot\Vendor\Weglot\Client\Api\Enum\WordType;
use Weglot\Vendor\Weglot\Client\Api\Exception\ApiError;
use Weglot\Vendor\Weglot\Client\Api\Exception\InputAndOutputCountMatchException;
use Weglot\Vendor\Weglot\Client\Api\Exception\InvalidWordTypeException;
use Weglot\Vendor\Weglot\Client\Api\Exception\MissingRequiredParamException;
use Weglot\Vendor\Weglot\Client\Api\Exception\MissingWordsOutputException;
use Weglot\Vendor\Weglot\Client\Api\TranslateEntry;
use Weglot\Vendor\Weglot\Client\Api\WordCollection;
use Weglot\Vendor\Weglot\Client\Api\WordEntry;
use Weglot\Vendor\Weglot\Client\Client;
use Weglot\Vendor\Weglot\Client\Endpoint\CdnTranslate;
use Weglot\Vendor\Weglot\Client\Endpoint\Translate;
use Weglot\Vendor\Weglot\Parser\Check\DomCheckerProvider;
use Weglot\Vendor\Weglot\Parser\Check\Regex\JsonChecker;
use Weglot\Vendor\Weglot\Parser\Check\RegexCheckerProvider;
use Weglot\Vendor\Weglot\Parser\ConfigProvider\ConfigProviderInterface;
use Weglot\Vendor\Weglot\Parser\ConfigProvider\ServerConfigProvider;
use Weglot\Vendor\Weglot\Parser\Formatter\DomFormatter;
use Weglot\Vendor\Weglot\Parser\Formatter\ExcludeBlocksFormatter;
use Weglot\Vendor\Weglot\Parser\Formatter\IgnoredNodes;
use Weglot\Vendor\Weglot\Parser\Formatter\JsonFormatter;
use Weglot\Vendor\Weglot\Util\SourceType;
use Weglot\Vendor\Weglot\Util\Text;
use Weglot\Vendor\WGSimpleHtmlDom\simple_html_dom;
/**
 * @phpstan-type HtmlTree = array{
 *     type: SourceType::SOURCE_HTML,
 *     source: string,
 *     dom: simple_html_dom,
 *     nodes: array,
 *     regexes: array,
 * }
 * @phpstan-type JsonTree = array{
 *      type: SourceType::SOURCE_JSON,
 *      source: string,
 *      jsonArray: string,
 *      paths: array,
 *  }
 * @phpstan-type TextTree = array{
 *     type: SourceType::SOURCE_TEXT,
 *     source: string|null,
 *     text: string,
 * }
 */
class Parser
{
    /**
     * Attribute to match in DOM when we don't want to translate innertext & childs.
     */
    const ATTRIBUTE_NO_TRANSLATE = 'data-wg-notranslate';
    const ATTRIBUTE_TRANSLATE = 'data-wg-translate';
    const ATTRIBUTE_TRANSLATE_INSIDE_BLOCKS = 'data-wg-translate-inside-blocks';
    /**
     * @var Client
     */
    protected $client;
    /**
     * @var ConfigProviderInterface
     */
    protected $configProvider;
    /**
     * @var array
     */
    protected $excludeBlocks;
    /**
     * @var array
     */
    protected $whiteList;
    /**
     * @var array
     */
    protected $translateInsideExclusionsBlocks;
    /**
     * @var array
     */
    protected $customSwitchers;
    /**
     * @var string
     */
    protected $languageFrom;
    /**
     * @var string
     */
    protected $languageTo;
    /**
     * @var WordCollection
     */
    protected $words;
    /**
     * @var DomCheckerProvider
     */
    protected $domCheckerProvider;
    /**
     * @var RegexCheckerProvider
     */
    protected $regexCheckerProvider;
    /**
     * @var IgnoredNodes
     */
    protected $ignoredNodesFormatter;
    public function __construct(Client $client, ConfigProviderInterface $config, array $excludeBlocks = [], array $customSwitchers = [], array $whiteList = [], array $translateInsideExclusionsBlocks = [])
    {
        $this->setClient($client)->setConfigProvider($config)->setExcludeBlocks($excludeBlocks)->setWhiteList($whiteList)->setTranslateInsideExclusionsBlocks($translateInsideExclusionsBlocks)->setCustomSwitchers($customSwitchers)->setWords(new WordCollection())->setDomCheckerProvider(new DomCheckerProvider($this, $client->getProfile()->getTranslationEngine()))->setRegexCheckerProvider(new RegexCheckerProvider($this))->setIgnoredNodesFormatter(new IgnoredNodes());
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
     * @return $this
     */
    public function setExcludeBlocks(array $excludeBlocks)
    {
        $this->excludeBlocks = $excludeBlocks;
        return $this;
    }
    /**
     * @return array
     */
    public function getExcludeBlocks()
    {
        return $this->excludeBlocks;
    }
    /**
     * @return $this
     */
    public function setWhiteList(array $whiteList)
    {
        $this->whiteList = $whiteList;
        return $this;
    }
    /**
     * @return array
     */
    public function getWhiteList()
    {
        return $this->whiteList;
    }
    /**
     * @return array
     */
    public function getTranslateInsideExclusionsBlocks()
    {
        return $this->translateInsideExclusionsBlocks;
    }
    /**
     * @return $this
     */
    public function setTranslateInsideExclusionsBlocks(array $translateInsideExclusionsBlocks)
    {
        $this->translateInsideExclusionsBlocks = $translateInsideExclusionsBlocks;
        return $this;
    }
    /**
     * @return $this
     */
    public function setCustomSwitchers(array $customSwitchers)
    {
        $this->customSwitchers = $customSwitchers;
        return $this;
    }
    /**
     * @return array
     */
    public function getCustomSwitchers()
    {
        return $this->customSwitchers;
    }
    /**
     * @return $this
     */
    public function setConfigProvider(ConfigProviderInterface $config)
    {
        $this->configProvider = $config;
        return $this;
    }
    /**
     * @return ConfigProviderInterface
     */
    public function getConfigProvider()
    {
        return $this->configProvider;
    }
    /**
     * @param string $languageFrom
     *
     * @return $this
     */
    public function setLanguageFrom($languageFrom)
    {
        $this->languageFrom = $languageFrom;
        return $this;
    }
    /**
     * @return string
     */
    public function getLanguageFrom()
    {
        return $this->languageFrom;
    }
    /**
     * @param string $languageTo
     *
     * @return $this
     */
    public function setLanguageTo($languageTo)
    {
        $this->languageTo = $languageTo;
        return $this;
    }
    /**
     * @return string
     */
    public function getLanguageTo()
    {
        return $this->languageTo;
    }
    /**
     * @return $this
     */
    public function setWords(WordCollection $wordCollection)
    {
        $this->words = $wordCollection;
        return $this;
    }
    /**
     * @return WordCollection
     */
    public function getWords()
    {
        return $this->words;
    }
    /**
     * @return $this
     */
    public function setRegexCheckerProvider(RegexCheckerProvider $regexCheckerProvider)
    {
        $this->regexCheckerProvider = $regexCheckerProvider;
        return $this;
    }
    /**
     * @return RegexCheckerProvider
     */
    public function getRegexCheckerProvider()
    {
        return $this->regexCheckerProvider;
    }
    /**
     * @return $this
     */
    public function setDomCheckerProvider(DomCheckerProvider $domCheckerProvider)
    {
        $this->domCheckerProvider = $domCheckerProvider;
        return $this;
    }
    /**
     * @return DomCheckerProvider
     */
    public function getDomCheckerProvider()
    {
        return $this->domCheckerProvider;
    }
    /**
     * @return $this
     */
    public function setIgnoredNodesFormatter(IgnoredNodes $ignoredNodesFormatter)
    {
        $this->ignoredNodesFormatter = $ignoredNodesFormatter;
        return $this;
    }
    /**
     * @return IgnoredNodes
     */
    public function getIgnoredNodesFormatter()
    {
        return $this->ignoredNodesFormatter;
    }
    /**
     * @param string $source
     * @param string $languageFrom
     * @param string $languageTo
     * @param array  $extraKeys
     * @param string $canonical
     * @param string $requestUrl
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
     * @param string $source
     * @param array  $extraKeys
     *
     * @return array{tree: HtmlTree|JsonTree|TextTree|string, words: WordCollection}
     *
     * @throws InvalidWordTypeException
     */
    public function parse($source, $extraKeys = [])
    {
        $type = self::getSourceType($source);
        if (SourceType::SOURCE_HTML === $type) {
            $tree = $this->parseHTML($source);
        } elseif (SourceType::SOURCE_JSON === $type) {
            $tree = $this->parseJSON($source, $extraKeys);
        } else {
            $tree = $this->parseText($source);
        }
        return ['tree' => $tree, 'words' => $this->getWords()];
    }
    /**
     * @param string $source
     *
     * @phpstan-return HtmlTree|string
     */
    public function parseHTML($source)
    {
        if (2 == $this->client->getProfile()->getTranslationEngine()) {
            $ignoredNodesFormatter = $this->getIgnoredNodesFormatter();
            $ignoredNodesFormatter->setSource($source)->handle();
            $source = $ignoredNodesFormatter->getSource();
        }
        // simple_html_dom
        $dom = \Weglot\Vendor\WGSimpleHtmlDom\str_get_html($source, \true, \true, WG_DEFAULT_TARGET_CHARSET, \false);
        // if simple_html_dom can't parse the $source, it returns false
        // so we just return raw $source
        if (\false === $dom) {
            return $source;
        }
        // if whiteList list is not empty we add attr wg-mode-whitelist to the body
        if (!empty($this->whiteList)) {
            foreach ($dom->find('body') as $item) {
                $item->setAttribute('wg-mode-whitelist', '');
            }
        }
        if (!empty($this->excludeBlocks)) {
            $excludeBlocks = new ExcludeBlocksFormatter($dom, $this->excludeBlocks, $this->whiteList, $this->translateInsideExclusionsBlocks);
            $dom = $excludeBlocks->getDom();
        }
        // checkers
        list($nodes, $regexes) = $this->checkers($dom, $source);
        return ['type' => SourceType::SOURCE_HTML, 'source' => $source, 'dom' => $dom, 'nodes' => $nodes, 'regexes' => $regexes];
    }
    /**
     * @param string $jsonString
     * @param array  $extraKeys
     *
     * @phpstan-return JsonTree
     *
     * @throws InvalidWordTypeException
     */
    public function parseJSON($jsonString, $extraKeys = [])
    {
        $checker = new JsonChecker($this, $jsonString, $extraKeys);
        return $checker->handle();
    }
    /**
     * @param string      $text
     * @param string|null $regex
     *
     * @phpstan-return TextTree
     *
     * @throws InvalidWordTypeException
     */
    public function parseText($text, $regex = null)
    {
        $this->getWords()->addOne(new WordEntry($text, WordType::TEXT));
        return ['type' => SourceType::SOURCE_TEXT, 'source' => $regex, 'text' => $text];
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
    /**
     * @param simple_html_dom $dom
     * @param string          $source
     *
     * @return array
     *
     * @throws InvalidWordTypeException
     */
    protected function checkers($dom, $source)
    {
        $nodes = $this->getDomCheckerProvider()->handle($dom);
        $regexes = $this->getRegexCheckerProvider()->handle($source);
        return [$nodes, $regexes];
    }
    /**
     * @param string                            $source
     * @param HtmlTree|JsonTree|TextTree|string $tree
     * @param int                               $index
     *
     * @return string
     */
    public function formatters($source, TranslateEntry $translateEntry, $tree, &$index = 0)
    {
        if (empty($tree['type'])) {
            return $source;
        }
        if (SourceType::SOURCE_TEXT === $tree['type']) {
            $source = str_replace($tree['text'], $translateEntry->getOutputWords()[$index]->getWord(), $source);
            ++$index;
        }
        if (SourceType::SOURCE_JSON === $tree['type']) {
            $formatter = new JsonFormatter($this, $source, $translateEntry);
            $formatter->handle($tree, $index);
            $source = $formatter->getSource();
        }
        if (SourceType::SOURCE_HTML === $tree['type']) {
            $formatter = new DomFormatter($this, $translateEntry);
            $formatter->handle($tree['nodes'], $index);
            $source = $tree['dom']->save();
            foreach ($tree['regexes'] as $regex) {
                if (empty($regex['source'])) {
                    continue;
                }
                $translatedRegex = $this->formatters($regex['source'], $translateEntry, $regex, $index);
                if (isset($regex['revert_callback']) && $regex['revert_callback']) {
                    $translatedRegex = \call_user_func($regex['revert_callback'], $translatedRegex);
                }
                if (SourceType::SOURCE_TEXT === $regex['type'] && $regex['source'] == $regex['text']) {
                    $source = preg_replace('#\b' . preg_quote($regex['source'], '#') . '\b#', $translatedRegex, $source);
                } else {
                    $source = str_replace($regex['source_before_callback'], $translatedRegex, $source);
                }
            }
        }
        return $source;
    }
    /**
     * @param string $source
     *
     * @return string
     */
    public static function getSourceType($source)
    {
        if (Text::isJSON($source)) {
            return SourceType::SOURCE_JSON;
        }
        if (Text::isHTML($source)) {
            return SourceType::SOURCE_HTML;
        }
        return SourceType::SOURCE_TEXT;
    }
}
