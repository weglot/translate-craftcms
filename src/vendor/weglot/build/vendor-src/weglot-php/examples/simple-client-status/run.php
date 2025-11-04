<?php

namespace Weglot\Vendor;

require_once __DIR__ . '/vendor/autoload.php';
use Weglot\Vendor\GuzzleHttp\Exception\GuzzleException;
use Weglot\Vendor\Weglot\Client\Client;
use Weglot\Vendor\Weglot\Client\Endpoint\Status;
// DotEnv
$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();
// Client
$client = new Client(\getenv('WG_API_KEY'));
$status = new Status($client);
// Run API :)
try {
    $object = $status->handle();
} catch (GuzzleException $e) {
    // network issues
    exit($e->getMessage());
}
// dumping returned object
\var_dump($object);
