<?php

namespace Weglot\Vendor;

require_once __DIR__ . '/vendor/autoload.php';
use Weglot\Vendor\Cache\Adapter\Predis\PredisCachePool;
use Weglot\Vendor\GuzzleHttp\Exception\GuzzleException;
use Weglot\Vendor\Predis\Client as Redis;
use Weglot\Vendor\Weglot\Client\Api\Enum\BotType;
use Weglot\Vendor\Weglot\Client\Api\Enum\WordType;
use Weglot\Vendor\Weglot\Client\Api\Exception\InputAndOutputCountMatchException;
use Weglot\Vendor\Weglot\Client\Api\Exception\InvalidWordTypeException;
use Weglot\Vendor\Weglot\Client\Api\Exception\MissingRequiredParamException;
use Weglot\Vendor\Weglot\Client\Api\Exception\MissingWordsOutputException;
use Weglot\Vendor\Weglot\Client\Api\TranslateEntry;
use Weglot\Vendor\Weglot\Client\Api\WordEntry;
use Weglot\Vendor\Weglot\Client\Client;
use Weglot\Vendor\Weglot\Client\Endpoint\Translate;
// DotEnv
$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();
// Caching
$redis = new Redis(['scheme' => \getenv('REDIS_SCHEME'), 'host' => \getenv('REDIS_HOST'), 'port' => \getenv('REDIS_PORT')]);
$redisPool = new PredisCachePool($redis);
// TranslateEntry
$params = ['language_from' => 'en', 'language_to' => 'de', 'title' => 'Weglot | Translate your website - Multilingual for WordPress, Shopify, ...', 'request_url' => 'https://weglot.com/', 'bot' => BotType::HUMAN];
try {
    $translate = new TranslateEntry($params);
    $translate->getInputWords()->addOne(new WordEntry('This is a blue car', WordType::TEXT))->addOne(new WordEntry('This is a black car', WordType::TEXT));
} catch (InvalidWordTypeException $e) {
    // input params issues, WordType on WordEntry construct needs to be valid
    exit($e->getMessage());
} catch (MissingRequiredParamException $e) {
    // input params issues, just need to have required fields
    exit($e->getMessage());
}
// Client
$client = new Client(\getenv('WG_API_KEY'));
$client->setCacheItemPool($redisPool);
$translate = new Translate($translate, $client);
// Run API :)
try {
    $object = $translate->handle();
} catch (InvalidWordTypeException $e) {
    // input params issues, shouldn't happen on server response
    exit($e->getMessage());
} catch (MissingRequiredParamException $e) {
    // input params issues, shouldn't happen on server response
    exit($e->getMessage());
} catch (MissingWordsOutputException $e) {
    // api return doesn't contains "to_words", shouldn't happen on server response
    exit($e->getMessage());
} catch (InputAndOutputCountMatchException $e) {
    // api return doesn't contains same number of input & output words, shouldn't happen on server response
    exit($e->getMessage());
} catch (GuzzleException $e) {
    // network issues
    exit($e->getMessage());
}
// dumping returned object
\var_dump($object);
