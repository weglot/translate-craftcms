<?php

namespace Weglot\Vendor;

use Weglot\Vendor\Symfony\Component\Dotenv\Dotenv;
require \dirname(__DIR__) . '/vendor/autoload.php';
if (\file_exists(\dirname(__DIR__) . '/config/bootstrap.php')) {
    require \dirname(__DIR__) . '/config/bootstrap.php';
} else {
    (new Dotenv())->bootEnv(\dirname(__DIR__) . '/.env');
}
// When using GitHub actions secrets are hydrated in the server global
// but not the env one, so we merge the $_SERVER inside the $_ENV one.
$_ENV += $_SERVER;
if ($_SERVER['APP_DEBUG']) {
    \umask(0);
}
