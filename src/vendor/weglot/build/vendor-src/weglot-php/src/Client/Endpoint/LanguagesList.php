<?php

namespace Weglot\Vendor\Weglot\Client\Endpoint;

use Weglot\Vendor\Weglot\Client\Api\LanguageCollection;
use Weglot\Vendor\Weglot\Client\Factory\Languages as LanguagesFactory;
use Weglot\Vendor\WeglotLanguages\Languages;
/**
 * @phpstan-import-type Language from Languages
 */
class LanguagesList extends Endpoint
{
    const METHOD = 'GET';
    const ENDPOINT = '/languages';
    /**
     * @return array<string, Language>
     */
    public function getLanguages()
    {
        return Languages::getData();
    }
    /**
     * @return LanguageCollection
     */
    public function handle()
    {
        $languageCollection = new LanguageCollection();
        $data = $this->getLanguages();
        $data = array_map(function ($data) {
            $external_code = $data['code'];
            if ('tw' == $external_code) {
                $external_code = 'zh-tw';
            }
            if ('br' == $external_code) {
                $external_code = 'pt-br';
            }
            if ('sa' == $external_code) {
                $external_code = 'sr-lt';
            }
            return ['internal_code' => $data['code'], 'english' => $data['english'], 'local' => $data['local'], 'rtl' => $data['rtl'], 'external_code' => $external_code];
        }, $data);
        foreach ($data as $language) {
            if ('fc' != $language['internal_code']) {
                $factory = new LanguagesFactory($language);
                $languageCollection->addOne($factory->handle());
            }
        }
        return $languageCollection;
    }
}
