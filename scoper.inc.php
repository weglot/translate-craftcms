<?php

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

return [
    'prefix' => 'Weglot\\Vendor',
    'finders' => [
        (new Finder())
            ->files()
            ->in(__DIR__.'/dependencies')
            ->name('*.php')
            ->ignoreVCS(true),
    ],
    'exclude-namespaces' => ['Weglot\\Craft', 'craft', 'yii'],
    'expose-namespaces' => [
        'Psr\\Cache',
    ],
    'expose-classes' => [
        'Psr\\Cache\\*', // <- wildcard
    ],
    'expose-functions' => [],
    'expose-constants' => [],

    'patchers' => [
        static function (string $filePath, string $prefix, string $content): string {
            if (str_contains($filePath, 'weglot-php/src/Parser/Check/DomCheckerProvider.php')) {
                $content = str_replace(
                    '\\Weglot\\Parser\\Check\\Dom\\\\',
                    '\\Weglot\\Vendor\\Weglot\\Parser\\Check\\Dom\\\\',
                    $content
                );
            }
            $content = str_replace('Weglot\\Vendor\\Psr\\Cache\\', 'Psr\\Cache\\', $content);

            return $content;
        },
    ],
];
