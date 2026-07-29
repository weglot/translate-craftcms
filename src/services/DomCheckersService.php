<?php

declare(strict_types=1);

namespace weglot\craftweglot\services;

use craft\base\Component;

class DomCheckersService extends Component
{
    /**
     * @return list<string> list of fully-qualified checker class names (feeds DomCheckerProvider::addCheckers(), typed array<int, mixed>)
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

        // TODO: Replace with a Craft event to allow extension
        return $checkerClasses;
    }
}
