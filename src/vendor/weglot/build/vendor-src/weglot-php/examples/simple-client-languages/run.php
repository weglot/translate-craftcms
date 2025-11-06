<?php

namespace Weglot\Vendor;

require_once __DIR__ . '/vendor/autoload.php';
use Weglot\Vendor\Weglot\Client\Client;
// DotEnv
$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();
// Client
$client = new Client(\getenv('WG_API_KEY'));
$languages = new Languages($client);
// Run API :)
$object = $languages->handle();
// dumping returned object
\var_dump($object->getCode('fi'));
