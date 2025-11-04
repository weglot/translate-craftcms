<?php

declare (strict_types=1);
namespace Weglot\Vendor;

use Weglot\Vendor\Isolated\Symfony\Component\Finder\Finder;
return ['prefix' => 'Weglot\Vendor\Weglot\Vendor', 'output-dir' => 'build/scoped-vendor', 'finders' => [(new Finder())->files()->in('build/vendor-src')->name('*.php')->ignoreVCS(\true), (new Finder())->files()->in('build/vendor-src/weglot-php/data')->ignoreVCS(\true)->ignoreDotFiles(\false)], 'exclude-namespaces' => ['Weglot\Vendor\Weglot\Craft', 'craft', 'yii'], 'expose-namespaces' => ['Weglot\Vendor\Psr\Cache'], 'expose-classes' => ['Psr\Cache\*'], 'expose-functions' => [], 'expose-constants' => [], 'patchers' => [static function (string $filePath, string $prefix, string $content): string {
    if (\str_contains($filePath, 'weglot-php/src/Parser/Check/DomCheckerProvider.php')) {
        $content = \str_replace('\Weglot\Parser\Check\Dom\\\\', '\Weglot\Vendor\Weglot\Parser\Check\Dom\\\\', $content);
    }
    $content = \str_replace('Psr\Cache\\', 'Psr\Cache\\', $content);
    return $content;
}]];
