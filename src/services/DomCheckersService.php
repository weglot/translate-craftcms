<?php

namespace weglot\craftweglot\services;

use Craft;
use craft\base\Component;

class DomCheckersService extends Component
{
    /**
     * @return string[]
     */
    public function getDomCheckers(): array
    {
        $checkersDir = \Craft::getAlias('@weglot/craftweglot/checkers/dom');
        if (!is_dir($checkersDir)) {
            return [];
        }

        $files = array_diff(scandir($checkersDir), ['..', '.']);
        $checkerClasses = [];

        foreach ($files as $file) {
            if ('php' !== pathinfo($file, \PATHINFO_EXTENSION)) {
                continue;
            }
            $className = pathinfo($file, \PATHINFO_FILENAME);
            $checkerClasses[] = '\\weglot\\craftweglot\\checkers\\dom\\'.$className;
        }

        // TODO: Remplacer par un événement Craft pour permettre l'extension
        return $checkerClasses;
    }
}
