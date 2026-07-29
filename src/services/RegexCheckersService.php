<?php

declare(strict_types=1);

namespace weglot\craftweglot\services;

use craft\base\Component;
use Weglot\Vendor\Weglot\Parser\Check\Regex\RegexChecker;

class RegexCheckersService extends Component
{
    /**
     * @return list<RegexChecker> list of RegexChecker instances (feeds RegexCheckerProvider::addCheckers(), typed array<int, RegexChecker>)
     */
    public function getRegexCheckers(): array
    {
        // TODO: Replace with a Craft event to allow adding words/rules
        return [];
    }
}
