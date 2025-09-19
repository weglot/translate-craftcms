<?php
declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $config): void {
	// Cible le code du plugin uniquement
	$config->paths([__DIR__ . '/src']);

	// Règles Craft CMS 5
	$config->import(__DIR__ . '/vendor/craftcms/rector/sets/craft-cms-50.php');

	// Sets génériques stables
	$config->import(LevelSetList::UP_TO_PHP_82);
	$config->import(SetList::CODE_QUALITY);
	$config->import(SetList::DEAD_CODE);

	// Si ta version expose aussi ce set, tu peux l’ajouter:
	// $config->import(SetList::TYPE_DECLARATION);

	// Exclusions
	$config->skip([
		__DIR__ . '/vendor',
		__DIR__ . '/node_modules',
		__DIR__ . '/tests',
		NullToStrictStringFuncCallArgRector::class,
	]);
};