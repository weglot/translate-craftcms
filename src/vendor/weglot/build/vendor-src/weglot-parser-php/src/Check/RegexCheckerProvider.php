<?php

namespace Weglot\Vendor\Weglot\Parser\Check;

use Weglot\Vendor\Weglot\Parser\Check\Regex\RegexChecker;
use Weglot\Vendor\Weglot\Parser\Definitions\Exception\InvalidWordTypeException;
use Weglot\Vendor\Weglot\Parser\Parser;
use Weglot\Vendor\Weglot\Parser\Util\SourceType;
class RegexCheckerProvider
{
    public const DEFAULT_CHECKERS_NAMESPACE = '\Weglot\Parser\Check\Regex\\';
    /**
     * @var Parser
     */
    protected $parser;
    /**
     * @var array<int, RegexChecker>
     */
    protected $checkers = [];
    /**
     * @var array<mixed>
     */
    protected $discoverCaching = [];
    public function __construct(Parser $parser)
    {
        $this->setParser($parser);
        $this->loadDefaultCheckers();
    }
    /**
     * @return $this
     */
    public function setParser(Parser $parser)
    {
        $this->parser = $parser;
        return $this;
    }
    /**
     * @return Parser
     */
    public function getParser()
    {
        return $this->parser;
    }
    /**
     * @param RegexChecker $checker
     *
     * @return $this
     */
    public function addChecker($checker)
    {
        $this->checkers[] = $checker;
        return $this;
    }
    /**
     * @param array<int, RegexChecker> $checkers
     *
     * @return $this
     */
    public function addCheckers(array $checkers)
    {
        $this->checkers = array_merge($this->checkers, $checkers);
        return $this;
    }
    /**
     * @return array<int, RegexChecker>
     */
    public function getCheckers()
    {
        return $this->checkers;
    }
    protected function loadDefaultCheckers(): void
    {
        $jsonKeys = ['description', 'name', 'headline', 'articleSection', 'text'];
        if (\function_exists('Weglot\Vendor\apply_filters')) {
            $jsonKeys = apply_filters('list_json_ld_keys', $jsonKeys);
        }
        if (!str_contains(implode(',', $this->parser->getExcludeBlocks()), 'application/ld+json') && !str_contains(implode(',', $this->parser->getExcludeBlocks()), '.wg-ldjson')) {
            $this->addChecker(new RegexChecker("#<script type=('|\")application\\/ld\\+json('|\")([^\\>]+?)?>(.*?)<\\/script>#s", SourceType::SOURCE_JSON, 4, $jsonKeys));
        }
        if (!str_contains(implode(',', $this->parser->getExcludeBlocks()), 'text/html') && !str_contains(implode(',', $this->parser->getExcludeBlocks()), '.wg-texthtml')) {
            $this->addChecker(new RegexChecker("#<script type=('|\")text/html('|\")([^\\>]+?)?>(.+?)<\\/script>#s", SourceType::SOURCE_HTML, 4));
        }
    }
    /**
     * @param mixed $checker
     *
     * @return bool
     */
    public function register($checker)
    {
        if ($checker instanceof RegexChecker) {
            $this->addChecker($checker);
            return \true;
        }
        return \false;
    }
    /**
     * @param string $domString
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws InvalidWordTypeException
     */
    public function handle($domString)
    {
        $checkers = $this->getCheckers();
        $regexes = [];
        foreach ($checkers as $class) {
            list($regex, $type, $varNumber, $extraKeys, $callback, $revert_callback) = $class->toArray();
            if (!isset($revert_callback)) {
                $revert_callback = null;
            }
            preg_match_all($regex, $domString, $matches);
            if (isset($matches[$varNumber])) {
                $matches0 = $matches[0];
                $matches1 = $matches[$varNumber];
                foreach ($matches1 as $k => $match) {
                    $newMatch = $match;
                    if ($callback) {
                        $newMatch = \call_user_func($callback, $match);
                    }
                    if (SourceType::SOURCE_JSON === $type) {
                        $regex = $this->getParser()->parseJSON($newMatch, $extraKeys);
                        $regex['source_before_callback'] = $match;
                    }
                    if (SourceType::SOURCE_TEXT === $type) {
                        $regex = $this->getParser()->parseText($newMatch, $matches0[$k]);
                        $regex['source_before_callback'] = $matches0[$k];
                    }
                    if (SourceType::SOURCE_HTML === $type) {
                        $regex = $this->getParser()->parseHTML($newMatch);
                        $regex['source_before_callback'] = $match;
                    }
                    $regex['revert_callback'] = $revert_callback;
                    $regexes[] = $regex;
                }
            }
        }
        return $regexes;
    }
}
