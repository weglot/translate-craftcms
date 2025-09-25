<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
    ])
    ->withCache('.cache/rector')
    ->withPhpSets(php82: true)
    ->withPreparedSets(deadCode: true, codeQuality: true)
    ->withImportNames(importShortClasses: false)
    ->withSkip([
        NullToStrictStringFuncCallArgRector::class,
    ]);
