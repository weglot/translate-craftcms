<?php

namespace Weglot\Vendor\Weglot\Parser;

use Weglot\Vendor\Weglot\Parser\Check\DomCheckerProvider;
use Weglot\Vendor\Weglot\Parser\Check\Regex\JsonChecker;
use Weglot\Vendor\Weglot\Parser\Check\RegexCheckerProvider;
use Weglot\Vendor\Weglot\Parser\ConfigProvider\ConfigProviderInterface;
use Weglot\Vendor\Weglot\Parser\Definitions\Enum\WordType;
use Weglot\Vendor\Weglot\Parser\Definitions\Exception\InvalidWordTypeException;
use Weglot\Vendor\Weglot\Parser\Definitions\TranslateEntry;
use Weglot\Vendor\Weglot\Parser\Definitions\WordCollection;
use Weglot\Vendor\Weglot\Parser\Definitions\WordEntry;
use Weglot\Vendor\Weglot\Parser\Formatter\DomFormatter;
use Weglot\Vendor\Weglot\Parser\Formatter\ExcludeBlocksFormatter;
use Weglot\Vendor\Weglot\Parser\Formatter\IgnoredNodes;
use Weglot\Vendor\Weglot\Parser\Formatter\JsonFormatter;
use Weglot\Vendor\Weglot\Parser\Util\SourceType;
use Weglot\Vendor\Weglot\Parser\Util\Text;
use Weglot\Vendor\WGSimpleHtmlDom\simple_html_dom;
/**
 * @phpstan-type HtmlTree = array{
 *     type: SourceType::SOURCE_HTML,
 *     source: string,
 *     dom: simple_html_dom,
 *     nodes: array<int, array<string, mixed>>,
 *     regexes: array<int, array<string, mixed>>,
 * }
 * @phpstan-type JsonTree = array{
 *      type: SourceType::SOURCE_JSON,
 *      source: string,
 *      jsonArray: string,
 *      paths: array<int, array<string, mixed>>,
 *  }
 * @phpstan-type TextTree = array{
 *     type: SourceType::SOURCE_TEXT,
 *     source: string|null,
 *     text: string,
 * }
 */
class Parser
{
    public const ATTRIBUTE_NO_TRANSLATE = 'data-wg-notranslate';
    public const ATTRIBUTE_TRANSLATE = 'data-wg-translate';
    public const ATTRIBUTE_TRANSLATE_INSIDE_BLOCKS = 'data-wg-translate-inside-blocks';
    /**
     * @var int
     */
    protected $translationEngine;
    /**
     * @var ConfigProviderInterface
     */
    protected $configProvider;
    /**
     * @var array<string>
     */
    protected $excludeBlocks;
    /**
     * @var array<string>
     */
    protected $whiteList;
    /**
     * @var array<string>
     */
    protected $translateInsideExclusionsBlocks;
    /**
     * @var array<int, array<string, mixed>>
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
    /**
     * @param array<string>                    $excludeBlocks
     * @param array<int, array<string, mixed>> $customSwitchers
     * @param array<string>                    $whiteList
     * @param array<string>                    $translateInsideExclusionsBlocks
     */
    public function __construct(int $translationEngine, ConfigProviderInterface $config, array $excludeBlocks = [], array $customSwitchers = [], array $whiteList = [], array $translateInsideExclusionsBlocks = [])
    {
        $this->setTranslationEngine($translationEngine)->setConfigProvider($config)->setExcludeBlocks($excludeBlocks)->setWhiteList($whiteList)->setTranslateInsideExclusionsBlocks($translateInsideExclusionsBlocks)->setCustomSwitchers($customSwitchers)->setWords(new WordCollection())->setDomCheckerProvider(new DomCheckerProvider($this, $translationEngine))->setRegexCheckerProvider(new RegexCheckerProvider($this))->setIgnoredNodesFormatter(new IgnoredNodes());
    }
    /**
     * @return $this
     */
    public function setTranslationEngine(int $translationEngine)
    {
        $this->translationEngine = $translationEngine;
        return $this;
    }
    public function getTranslationEngine(): int
    {
        return $this->translationEngine;
    }
    /**
     * @param array<string> $excludeBlocks
     *
     * @return $this
     */
    public function setExcludeBlocks(array $excludeBlocks)
    {
        $this->excludeBlocks = $excludeBlocks;
        return $this;
    }
    /**
     * @return array<string>
     */
    public function getExcludeBlocks()
    {
        return $this->excludeBlocks;
    }
    /**
     * @param array<string> $whiteList
     *
     * @return $this
     */
    public function setWhiteList(array $whiteList)
    {
        $this->whiteList = $whiteList;
        return $this;
    }
    /**
     * @return array<string>
     */
    public function getWhiteList()
    {
        return $this->whiteList;
    }
    /**
     * @return array<string>
     */
    public function getTranslateInsideExclusionsBlocks()
    {
        return $this->translateInsideExclusionsBlocks;
    }
    /**
     * @param array<string> $translateInsideExclusionsBlocks
     *
     * @return $this
     */
    public function setTranslateInsideExclusionsBlocks(array $translateInsideExclusionsBlocks)
    {
        $this->translateInsideExclusionsBlocks = $translateInsideExclusionsBlocks;
        return $this;
    }
    /**
     * @param array<int, array<string, mixed>> $customSwitchers
     *
     * @return $this
     */
    public function setCustomSwitchers(array $customSwitchers)
    {
        $this->customSwitchers = $customSwitchers;
        return $this;
    }
    /**
     * @return array<int, array<string, mixed>>
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
     * @param string             $source
     * @param array<int, string> $extraKeys
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
        if (2 == $this->translationEngine) {
            $ignoredNodesFormatter = $this->getIgnoredNodesFormatter();
            $ignoredNodesFormatter->setSource($source)->handle();
            $source = $ignoredNodesFormatter->getSource();
        }
        $dom = \Weglot\Vendor\WGSimpleHtmlDom\str_get_html($source, \true, \true, WG_DEFAULT_TARGET_CHARSET, \false);
        if (\false === $dom) {
            return $source;
        }
        if (!empty($this->whiteList)) {
            foreach ($dom->find('body') as $item) {
                $item->setAttribute('wg-mode-whitelist', '');
            }
        }
        if (!empty($this->excludeBlocks)) {
            $excludeBlocks = new ExcludeBlocksFormatter($dom, $this->excludeBlocks, $this->whiteList, $this->translateInsideExclusionsBlocks);
            $dom = $excludeBlocks->getDom();
        }
        list($nodes, $regexes) = $this->checkers($dom, $source);
        return ['type' => SourceType::SOURCE_HTML, 'source' => $source, 'dom' => $dom, 'nodes' => $nodes, 'regexes' => $regexes];
    }
    /**
     * @param string             $jsonString
     * @param array<int, string> $extraKeys
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
     * @param simple_html_dom $dom
     * @param string          $source
     *
     * @return array{array<int, array<string, mixed>>, array<int, array<string, mixed>>}
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
            $outputWord = $translateEntry->getOutputWords()[$index] ?? null;
            if (null !== $outputWord) {
                $source = str_replace($tree['text'], $outputWord->getWord(), $source);
            }
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
