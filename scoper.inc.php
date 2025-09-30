<?php
declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

return [
	'prefix' => 'Weglot\\Vendor',
	'finders' => [
		(new Finder())->files()->in(__DIR__ . '/dependencies')->name('*.php')->ignoreVCS(true),
	],
	'exclude-namespaces' => ['Weglot\\Craft', 'craft', 'yii'],

	// 👇 patcher pour réécrire la constante
	'patchers' => [
		static function (string $filePath, string $prefix, string $content): string {
			if (str_contains($filePath, 'weglot-php/src/Parser/Check/DomCheckerProvider.php')) {
				$content = str_replace(
					"\\Weglot\\Parser\\Check\\Dom\\\\",
					"\\Weglot\\Vendor\\Weglot\\Parser\\Check\\Dom\\\\",
					$content
				);
			}
			return $content;
		},
	],
];
