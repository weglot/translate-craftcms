<?php

declare(strict_types=1);

use weglot\craftweglot\Plugin;

define('CRAFT_VENDOR_PATH', dirname(__DIR__).'/vendor');
define('CRAFT_BASE_PATH', dirname(__DIR__));
define('CRAFT_TESTS_PATH', __DIR__);
define('CRAFT_STORAGE_PATH', __DIR__.'/_craft/storage');
define('CRAFT_TEMPLATES_PATH', __DIR__.'/_craft/templates');
define('CRAFT_CONFIG_PATH', __DIR__.'/_craft/config');
define('CRAFT_MIGRATIONS_PATH', __DIR__.'/_craft/migrations');
define('CRAFT_TRANSLATIONS_PATH', __DIR__.'/_craft/translations');

// Load Composer's autoloader
require_once CRAFT_VENDOR_PATH.'/autoload.php';

// Load Craft
$appType = 'web';
Craft::$app = require __DIR__.'/vendor/craftcms/cms/bootstrap/bootstrap.php';

$plugin = new Plugin('');
$plugin->setComponents($plugin::config()['components']);
$plugin->init();
Plugin::setInstance($plugin);
