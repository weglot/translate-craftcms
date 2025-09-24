<?php

declare(strict_types=1);

use craft\ecs\SetList;
use PhpCsFixer\Fixer\ClassNotation\ClassAttributesSeparationFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return static function(ECSConfig $ecsConfig): void {
    $ecsConfig->paths([
        __DIR__ . '/src',
        __FILE__,
    ]);

    $ecsConfig->sets([
        SetList::CRAFT_CMS_4,
    ]);

    $ecsConfig->ruleWithConfiguration(ClassAttributesSeparationFixer::class, [
        'elements' => [
            'method' => 'one',
        ],
    ]);
};
