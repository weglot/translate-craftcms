<?php

namespace weglot\craftweglot\services;

use craft\base\Component;
use Weglot\Parser\Check\Regex\RegexChecker;

class RegexCheckersService extends Component
{
    /**
     * @return RegexChecker[]
     */
    public function getRegexCheckers(): array
    {
        // TODO: Replace with a Craft event to allow adding words/rules
        return [];
    }
}
