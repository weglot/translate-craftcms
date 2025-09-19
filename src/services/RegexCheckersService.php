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
        // TODO: Remplacer par un événement Craft pour permettre d'ajouter des mots/règles
        // TODO: Remplacer par un événement Craft pour permettre l'extension
        return [];
    }
}
