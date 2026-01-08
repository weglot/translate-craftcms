<?php

namespace Weglot\Parser\Check;

use Weglot\Client\Api\Exception\InvalidWordTypeException;
use Weglot\Parser\Check\Regex\RegexChecker;
use Weglot\Parser\Parser;
use Weglot\Util\SourceType;

class RegexCheckerProvider
{
    public const DEFAULT_CHECKERS_NAMESPACE = '\\Weglot\\Parser\\Check\\Regex\\';

    /**
     * @var Parser
     */
    protected $parser;

    /**
     * @var array
     */
    protected $checkers = [];

    /**
     * @var array
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
     * @return $this
     */
    public function addCheckers(array $checkers)
    {
        $this->checkers = array_merge($this->checkers, $checkers);

        return $this;
    }

    /**
     * @return array
     */
    public function getCheckers()
    {
        return $this->checkers;
    }

    /**
     * Load default checkers.
     *
     * @return void
     */
    protected function loadDefaultCheckers()
    {
        $jsonKeys = ['description', 'name', 'headline', 'articleSection', 'text'];

        // Dynamically extend keys for WordPress
        if (\function_exists('apply_filters')) {
            $jsonKeys = apply_filters('list_json_ld_keys', $jsonKeys);
        }

        /* Add JSON LD checker */
        if (!str_contains(implode(',', $this->parser->getExcludeBlocks()), 'application/ld+json')
           && !str_contains(implode(',', $this->parser->getExcludeBlocks()), '.wg-ldjson')
        ) {
            $this->addChecker(new RegexChecker("#<script type=('|\")application\/ld\+json('|\")([^\>]+?)?>(.*?)<\/script>#s", SourceType::SOURCE_JSON, 4, $jsonKeys));
        }

        /* Add HTML template checker */
        if (!str_contains(implode(',', $this->parser->getExcludeBlocks()), 'text/html')
           && !str_contains(implode(',', $this->parser->getExcludeBlocks()), '.wg-texthtml')
        ) {
            $this->addChecker(new RegexChecker("#<script type=('|\")text/html('|\")([^\>]+?)?>(.+?)<\/script>#s", SourceType::SOURCE_HTML, 4));
        }
    }

    /**
     * @param mixed $checker Class of the Checker to add
     *
     * @return bool
     */
    public function register($checker)
    {
        if ($checker instanceof RegexChecker) {
            $this->addChecker($checker);

            return true;
        }

        return false;
    }

    /**
     * @param string $domString
     *
     * @return array
     *
     * @throws InvalidWordTypeException
     */
    public function handle($domString)
    {
        $checkers = $this->getCheckers();
        $regexes = [];
        foreach ($checkers as $class) {
            [$regex, $type, $varNumber, $extraKeys, $callback, $revert_callback] = $class->toArray();
            // Ensure revert_callback is always initialized
            if (!isset($revert_callback)) {
                $revert_callback = null; // or any default value that makes sense in your context
            }
            preg_match_all($regex, $domString, $matches);
            if (isset($matches[$varNumber])) {
                $matches0 = $matches[0];
                $matches1 = $matches[$varNumber];
                foreach ($matches1 as $k => $match) {
                    $new_match = $match;
                    if ($callback) {
                        $new_match = \call_user_func($callback, $match);
                    }

                    if (SourceType::SOURCE_JSON === $type) {
                        $regex = $this->getParser()->parseJSON($new_match, $extraKeys);
                        $regex['source_before_callback'] = $match;
                    }
                    if (SourceType::SOURCE_TEXT === $type) {
                        $regex = $this->getParser()->parseText($new_match, $matches0[$k]);
                        $regex['source_before_callback'] = $matches0[$k];
                    }
                    if (SourceType::SOURCE_HTML === $type) {
                        $regex = $this->getParser()->parseHTML($new_match);
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
