<?php

namespace Weglot\Vendor\Weglot\Tests\Util;

use Weglot\Vendor\PHPUnit\Framework\TestCase;
use Weglot\Vendor\Weglot\Client\Api\LanguageEntry;
use Weglot\Vendor\Weglot\Util\Regex;
use Weglot\Vendor\Weglot\Util\Regex\RegexEnum;
use Weglot\Vendor\Weglot\Util\Url;
class UrlTest extends TestCase
{
    /**
     * @var LanguageEntry[]
     */
    protected $languages;
    protected function setUp(): void
    {
        $this->languages = ['en' => new LanguageEntry('en', 'en', 'English', 'English', \false), 'fr' => new LanguageEntry('fr', 'fr', 'French', 'Français', \false), 'es' => new LanguageEntry('es', 'es', 'Spanish', 'Espanol', \false), 'de' => new LanguageEntry('de', 'de', 'German', 'Deutsch', \false), 'kr' => new LanguageEntry('kr', 'kr', 'unknown', 'unknown', \false)];
    }
    /**
     * @return void
     */
    public function testSimpleUrlDefaultEnWithEsUrl()
    {
        $profile = ['url' => 'https://weglot.com/es/pricing', 'default' => $this->languages['en'], 'languages' => [$this->languages['fr'], $this->languages['de'], $this->languages['es']], 'prefix' => '', 'exclude' => [], 'results' => ['getHost' => 'https://weglot.com', 'getPathPrefix' => '', 'getPath' => '/pricing', 'getCurrentLanguage' => $this->languages['es'], 'detectBaseUrl' => 'https://weglot.com/pricing', 'getAllUrls' => [['language' => $this->languages['en'], 'url' => 'https://weglot.com/pricing', 'excluded' => \false, 'exclusion_behavior' => 'NOT_TRANSLATED', 'language_button_displayed' => \true], ['language' => $this->languages['fr'], 'url' => 'https://weglot.com/fr/pricing', 'excluded' => \false, 'exclusion_behavior' => 'NOT_TRANSLATED', 'language_button_displayed' => \true], ['language' => $this->languages['de'], 'url' => 'https://weglot.com/de/pricing', 'excluded' => \false, 'exclusion_behavior' => 'NOT_TRANSLATED', 'language_button_displayed' => \true], ['language' => $this->languages['es'], 'url' => 'https://weglot.com/es/pricing', 'excluded' => \false, 'exclusion_behavior' => 'NOT_TRANSLATED', 'language_button_displayed' => \true]]]];
        $url = $this->_urlInstance($profile);
        $this->_checkResults($url, $profile);
    }
    /**
     * @return void
     */
    public function testSimpleUrlDefaultFrWithEnUrl()
    {
        $profile = ['url' => 'https://www.ratp.fr/en/horaires', 'default' => $this->languages['fr'], 'languages' => [$this->languages['en']], 'prefix' => '', 'exclude' => [], 'results' => ['getHost' => 'https://www.ratp.fr', 'getPathPrefix' => '', 'detectBaseUrl' => 'https://www.ratp.fr/horaires', 'getPath' => '/horaires', 'getCurrentLanguage' => $this->languages['en'], 'getAllUrls' => [['language' => $this->languages['fr'], 'url' => 'https://www.ratp.fr/horaires', 'excluded' => \false, 'exclusion_behavior' => 'NOT_TRANSLATED', 'language_button_displayed' => \true], ['language' => $this->languages['en'], 'url' => 'https://www.ratp.fr/en/horaires', 'excluded' => \false, 'exclusion_behavior' => 'NOT_TRANSLATED', 'language_button_displayed' => \true]]]];
        $url = $this->_urlInstance($profile);
        $this->_checkResults($url, $profile);
    }
    /**
     * @return void
     */
    public function testSimpleUrlDefaultFrWithEnUrlAndCustomPort()
    {
        $profile = ['url' => 'https://www.ratp.fr:3000/en/horaires', 'default' => $this->languages['fr'], 'languages' => [$this->languages['en']], 'prefix' => '', 'exclude' => [], 'results' => ['getHost' => 'https://www.ratp.fr:3000', 'getPathPrefix' => '', 'detectBaseUrl' => 'https://www.ratp.fr:3000/horaires', 'getPath' => '/horaires', 'getCurrentLanguage' => $this->languages['en'], 'getAllUrls' => [['language' => $this->languages['fr'], 'url' => 'https://www.ratp.fr:3000/horaires', 'excluded' => \false, 'exclusion_behavior' => 'NOT_TRANSLATED', 'language_button_displayed' => \true], ['language' => $this->languages['en'], 'url' => 'https://www.ratp.fr:3000/en/horaires', 'excluded' => \false, 'exclusion_behavior' => 'NOT_TRANSLATED', 'language_button_displayed' => \true]]]];
        $url = $this->_urlInstance($profile);
        $this->_checkResults($url, $profile);
    }
    /**
     * @return void
     */
    public function testSimpleUrlDefaultFrWithFrUrl()
    {
        $profile = ['url' => 'https://www.ratp.fr/horaires', 'default' => $this->languages['fr'], 'languages' => [$this->languages['en']], 'prefix' => '', 'exclude' => [], 'results' => ['getHost' => 'https://www.ratp.fr', 'getPathPrefix' => '', 'detectBaseUrl' => 'https://www.ratp.fr/horaires', 'getPath' => '/horaires', 'getCurrentLanguage' => $this->languages['fr'], 'getAllUrls' => [['language' => $this->languages['fr'], 'url' => 'https://www.ratp.fr/horaires', 'excluded' => \false, 'exclusion_behavior' => 'NOT_TRANSLATED', 'language_button_displayed' => \true], ['language' => $this->languages['en'], 'url' => 'https://www.ratp.fr/en/horaires', 'excluded' => \false, 'exclusion_behavior' => 'NOT_TRANSLATED', 'language_button_displayed' => \true]]]];
        $url = $this->_urlInstance($profile);
        $this->_checkResults($url, $profile);
    }
    /**
     * @return void
     */
    public function testUrlDefaultEnWithEsUrlAndPrefix()
    {
        $profile = ['url' => 'https://weglot.com/web/es/pricing', 'default' => $this->languages['en'], 'languages' => [$this->languages['fr'], $this->languages['de'], $this->languages['es']], 'prefix' => '/web', 'exclude' => [], 'results' => ['getHost' => 'https://weglot.com', 'getPathPrefix' => '/web', 'getPath' => '/pricing', 'getCurrentLanguage' => $this->languages['es'], 'detectBaseUrl' => 'https://weglot.com/web/pricing', 'getAllUrls' => [['language' => $this->languages['en'], 'url' => 'https://weglot.com/web/pricing', 'excluded' => \false, 'exclusion_behavior' => 'NOT_TRANSLATED', 'language_button_displayed' => \true], ['language' => $this->languages['fr'], 'url' => 'https://weglot.com/web/fr/pricing', 'excluded' => \false, 'exclusion_behavior' => 'NOT_TRANSLATED', 'language_button_displayed' => \true], ['language' => $this->languages['de'], 'url' => 'https://weglot.com/web/de/pricing', 'excluded' => \false, 'exclusion_behavior' => 'NOT_TRANSLATED', 'language_button_displayed' => \true], ['language' => $this->languages['es'], 'url' => 'https://weglot.com/web/es/pricing', 'excluded' => \false, 'exclusion_behavior' => 'NOT_TRANSLATED', 'language_button_displayed' => \true]]]];
        $url = $this->_urlInstance($profile);
        $this->_checkResults($url, $profile);
    }
    /**
     * @return void
     */
    public function testUrlDefaultEnWithEsUrlAndTrailingSlashAndPrefix()
    {
        $profile = ['url' => 'http://weglotmultiv2.local/othersite/', 'default' => $this->languages['en'], 'languages' => [$this->languages['fr'], $this->languages['de'], $this->languages['es']], 'prefix' => '/othersite', 'exclude' => [], 'results' => ['getHost' => 'http://weglotmultiv2.local', 'getPathPrefix' => '/othersite', 'getPath' => '/', 'getCurrentLanguage' => $this->languages['en'], 'detectBaseUrl' => 'http://weglotmultiv2.local/othersite/', 'getAllUrls' => [['language' => $this->languages['en'], 'url' => 'http://weglotmultiv2.local/othersite/', 'excluded' => \false, 'exclusion_behavior' => 'NOT_TRANSLATED', 'language_button_displayed' => \true], ['language' => $this->languages['fr'], 'url' => 'http://weglotmultiv2.local/othersite/fr/', 'excluded' => \false, 'exclusion_behavior' => 'NOT_TRANSLATED', 'language_button_displayed' => \true], ['language' => $this->languages['de'], 'url' => 'http://weglotmultiv2.local/othersite/de/', 'excluded' => \false, 'exclusion_behavior' => 'NOT_TRANSLATED', 'language_button_displayed' => \true], ['language' => $this->languages['es'], 'url' => 'http://weglotmultiv2.local/othersite/es/', 'excluded' => \false, 'exclusion_behavior' => 'NOT_TRANSLATED', 'language_button_displayed' => \true]]]];
        $url = $this->_urlInstance($profile);
        $this->_checkResults($url, $profile);
    }
    /**
     * @return void
     */
    public function testUrlDefaultEnWithEnUrlAndPrefixAsUrl()
    {
        $profile = ['url' => 'https://weglot.com/web', 'default' => $this->languages['en'], 'languages' => [$this->languages['fr'], $this->languages['de'], $this->languages['es']], 'prefix' => '/web', 'exclude' => [], 'results' => ['getHost' => 'https://weglot.com', 'getPathPrefix' => '/web', 'getPath' => '/', 'getCurrentLanguage' => $this->languages['en'], 'detectBaseUrl' => 'https://weglot.com/web/', 'getAllUrls' => [['language' => $this->languages['en'], 'url' => 'https://weglot.com/web/', 'excluded' => \false, 'exclusion_behavior' => 'NOT_TRANSLATED', 'language_button_displayed' => \true], ['language' => $this->languages['fr'], 'url' => 'https://weglot.com/web/fr/', 'excluded' => \false, 'exclusion_behavior' => 'NOT_TRANSLATED', 'language_button_displayed' => \true], ['language' => $this->languages['de'], 'url' => 'https://weglot.com/web/de/', 'excluded' => \false, 'exclusion_behavior' => 'NOT_TRANSLATED', 'language_button_displayed' => \true], ['language' => $this->languages['es'], 'url' => 'https://weglot.com/web/es/', 'excluded' => \false, 'exclusion_behavior' => 'NOT_TRANSLATED', 'language_button_displayed' => \true]]]];
        $url = $this->_urlInstance($profile);
        $this->_checkResults($url, $profile);
    }
    /**
     * @return void
     */
    public function testUrlDefaultEnWithEnUrlAndPrefixAsUrlAndCustomPort()
    {
        $profile = ['url' => 'https://weglot.com:8080/web/es/', 'default' => $this->languages['en'], 'languages' => [$this->languages['fr'], $this->languages['de'], $this->languages['es']], 'prefix' => '/web', 'exclude' => [], 'results' => ['getHost' => 'https://weglot.com:8080', 'getPathPrefix' => '/web', 'getPath' => '/', 'getCurrentLanguage' => $this->languages['es'], 'detectBaseUrl' => 'https://weglot.com:8080/web/', 'getAllUrls' => [['language' => $this->languages['en'], 'url' => 'https://weglot.com:8080/web/', 'excluded' => \false, 'exclusion_behavior' => 'NOT_TRANSLATED', 'language_button_displayed' => \true], ['language' => $this->languages['fr'], 'url' => 'https://weglot.com:8080/web/fr/', 'excluded' => \false, 'exclusion_behavior' => 'NOT_TRANSLATED', 'language_button_displayed' => \true], ['language' => $this->languages['de'], 'url' => 'https://weglot.com:8080/web/de/', 'excluded' => \false, 'exclusion_behavior' => 'NOT_TRANSLATED', 'language_button_displayed' => \true], ['language' => $this->languages['es'], 'url' => 'https://weglot.com:8080/web/es/', 'excluded' => \false, 'exclusion_behavior' => 'NOT_TRANSLATED', 'language_button_displayed' => \true]]]];
        $url = $this->_urlInstance($profile);
        $this->_checkResults($url, $profile);
    }
    /**
     * @return void
     */
    public function testUrlDefaultEnWithFrAndExclude()
    {
        $profile = ['url' => 'https://weglot.com/fr/pricing', 'default' => $this->languages['en'], 'languages' => [$this->languages['fr'], $this->languages['kr']], 'prefix' => '', 'exclude' => [[new Regex(RegexEnum::MATCH_REGEX, '\/admin\/.*'), null]], 'results' => ['getHost' => 'https://weglot.com', 'getPathPrefix' => '', 'getPath' => '/pricing', 'getCurrentLanguage' => $this->languages['fr'], 'detectBaseUrl' => 'https://weglot.com/pricing', 'getAllUrls' => [['language' => $this->languages['en'], 'url' => 'https://weglot.com/pricing', 'excluded' => \false, 'exclusion_behavior' => 'NOT_TRANSLATED', 'language_button_displayed' => \true], ['language' => $this->languages['fr'], 'url' => 'https://weglot.com/fr/pricing', 'excluded' => \false, 'exclusion_behavior' => 'NOT_TRANSLATED', 'language_button_displayed' => \true], ['language' => $this->languages['kr'], 'url' => 'https://weglot.com/kr/pricing', 'excluded' => \false, 'exclusion_behavior' => 'NOT_TRANSLATED', 'language_button_displayed' => \true]]]];
        $url = $this->_urlInstance($profile);
        $this->_checkResults($url, $profile);
        $profile['url'] = 'https://weglot.com/fr/admin/dashboard';
        $profile['results']['getPath'] = '/admin/dashboard';
        $profile['results']['detectBaseUrl'] = 'https://weglot.com/admin/dashboard';
        $profile['results']['getAllUrls'] = [['language' => $this->languages['en'], 'url' => 'https://weglot.com/admin/dashboard', 'excluded' => \false, 'exclusion_behavior' => 'NOT_TRANSLATED', 'language_button_displayed' => \true], ['language' => $this->languages['fr'], 'url' => 'https://weglot.com/fr/admin/dashboard', 'excluded' => \true, 'exclusion_behavior' => 'NOT_TRANSLATED', 'language_button_displayed' => \true], ['language' => $this->languages['kr'], 'url' => 'https://weglot.com/kr/admin/dashboard', 'excluded' => \true, 'exclusion_behavior' => 'NOT_TRANSLATED', 'language_button_displayed' => \true]];
        $url = $this->_urlInstance($profile);
        $this->_checkResults($url, $profile);
    }
    /**
     * @return void
     */
    public function testUrlDefaultEnWithKrAndInverseExclude()
    {
        $profile = ['url' => 'https://weglot.com/kr/pricing', 'default' => $this->languages['en'], 'languages' => [$this->languages['fr'], $this->languages['kr']], 'prefix' => '', 'exclude' => [[new Regex(RegexEnum::MATCH_REGEX, '^(?!/rgpd-wordpress/?|/optimiser-wordpress/?).*$'), null]], 'results' => ['getHost' => 'https://weglot.com', 'getPathPrefix' => '', 'getPath' => '/pricing', 'getCurrentLanguage' => $this->languages['kr'], 'detectBaseUrl' => 'https://weglot.com/pricing', 'getAllUrls' => [['language' => $this->languages['en'], 'url' => 'https://weglot.com/pricing', 'excluded' => \false, 'exclusion_behavior' => 'NOT_TRANSLATED', 'language_button_displayed' => \true], ['language' => $this->languages['fr'], 'url' => 'https://weglot.com/fr/pricing', 'excluded' => \true, 'exclusion_behavior' => 'NOT_TRANSLATED', 'language_button_displayed' => \true], ['language' => $this->languages['kr'], 'url' => 'https://weglot.com/kr/pricing', 'excluded' => \true, 'exclusion_behavior' => 'NOT_TRANSLATED', 'language_button_displayed' => \true]]]];
        $url = $this->_urlInstance($profile);
        $this->_checkResults($url, $profile);
        $profile['url'] = 'https://weglot.com/kr/rgpd-wordpress';
        $profile['results']['getPath'] = '/rgpd-wordpress';
        $profile['results']['detectBaseUrl'] = 'https://weglot.com/rgpd-wordpress';
        $profile['results']['getAllUrls'] = [['language' => $this->languages['en'], 'url' => 'https://weglot.com/rgpd-wordpress', 'excluded' => \false, 'exclusion_behavior' => 'NOT_TRANSLATED', 'language_button_displayed' => \true], ['language' => $this->languages['fr'], 'url' => 'https://weglot.com/fr/rgpd-wordpress', 'excluded' => \false, 'exclusion_behavior' => 'NOT_TRANSLATED', 'language_button_displayed' => \true], ['language' => $this->languages['kr'], 'url' => 'https://weglot.com/kr/rgpd-wordpress', 'excluded' => \false, 'exclusion_behavior' => 'NOT_TRANSLATED', 'language_button_displayed' => \true]];
        $url = $this->_urlInstance($profile);
        $this->_checkResults($url, $profile);
    }
    /**
     * @return void
     */
    public function testUrlDefaultEnWithFrAndPrefixAndExclude()
    {
        $profile = ['url' => 'https://weglot.com/landing/fr/how-to-manage-your-translations', 'default' => $this->languages['en'], 'languages' => [$this->languages['fr'], $this->languages['kr']], 'prefix' => '/landing', 'exclude' => [[new Regex(RegexEnum::MATCH_REGEX, '\/admin\/.*'), null]], 'results' => ['getHost' => 'https://weglot.com', 'getPathPrefix' => '/landing', 'getPath' => '/how-to-manage-your-translations', 'getCurrentLanguage' => $this->languages['fr'], 'detectBaseUrl' => 'https://weglot.com/landing/how-to-manage-your-translations', 'getAllUrls' => [['language' => $this->languages['en'], 'url' => 'https://weglot.com/landing/how-to-manage-your-translations', 'excluded' => \false, 'exclusion_behavior' => 'NOT_TRANSLATED', 'language_button_displayed' => \true], ['language' => $this->languages['fr'], 'url' => 'https://weglot.com/landing/fr/how-to-manage-your-translations', 'excluded' => \false, 'exclusion_behavior' => 'NOT_TRANSLATED', 'language_button_displayed' => \true], ['language' => $this->languages['kr'], 'url' => 'https://weglot.com/landing/kr/how-to-manage-your-translations', 'excluded' => \false, 'exclusion_behavior' => 'NOT_TRANSLATED', 'language_button_displayed' => \true]]]];
        $url = $this->_urlInstance($profile);
        $this->_checkResults($url, $profile);
        $profile['url'] = 'https://weglot.com/landing/fr/admin/how-to-manage-your-translations';
        $profile['results']['getPath'] = '/admin/how-to-manage-your-translations';
        $profile['results']['detectBaseUrl'] = 'https://weglot.com/landing/admin/how-to-manage-your-translations';
        $profile['results']['getAllUrls'] = [['language' => $this->languages['en'], 'url' => 'https://weglot.com/landing/admin/how-to-manage-your-translations', 'excluded' => \false, 'exclusion_behavior' => 'NOT_TRANSLATED', 'language_button_displayed' => \true], ['language' => $this->languages['fr'], 'url' => 'https://weglot.com/landing/fr/admin/how-to-manage-your-translations', 'excluded' => \true, 'exclusion_behavior' => 'NOT_TRANSLATED', 'language_button_displayed' => \true], ['language' => $this->languages['kr'], 'url' => 'https://weglot.com/landing/kr/admin/how-to-manage-your-translations', 'excluded' => \true, 'exclusion_behavior' => 'NOT_TRANSLATED', 'language_button_displayed' => \true]];
        $url = $this->_urlInstance($profile);
        $this->_checkResults($url, $profile);
    }
    /**
     * @return void
     */
    public function testSimpleUrlDefaultFrWithEnUrlAndQuery()
    {
        $profile = ['url' => 'https://www.ratp.fr/en/horaires?from=2018-06-04&to=2018-06-05', 'default' => $this->languages['fr'], 'languages' => [$this->languages['en']], 'prefix' => '', 'exclude' => [], 'results' => ['getHost' => 'https://www.ratp.fr', 'getPathPrefix' => '', 'detectBaseUrl' => 'https://www.ratp.fr/horaires?from=2018-06-04&to=2018-06-05', 'getPath' => '/horaires', 'getCurrentLanguage' => $this->languages['en'], 'getAllUrls' => [['language' => $this->languages['fr'], 'url' => 'https://www.ratp.fr/horaires?from=2018-06-04&to=2018-06-05', 'excluded' => \false, 'exclusion_behavior' => 'NOT_TRANSLATED', 'language_button_displayed' => \true], ['language' => $this->languages['en'], 'url' => 'https://www.ratp.fr/en/horaires?from=2018-06-04&to=2018-06-05', 'excluded' => \false, 'exclusion_behavior' => 'NOT_TRANSLATED', 'language_button_displayed' => \true]]]];
        $url = $this->_urlInstance($profile);
        $this->_checkResults($url, $profile);
    }
    /**
     * @return Url
     */
    protected function _urlInstance(array $profile)
    {
        return new Url($profile['url'], $profile['default'], $profile['languages'], $profile['prefix'], $profile['exclude'], []);
    }
    /**
     * @return string
     */
    protected function _generateHrefLangs(array $currentRequestAllUrls)
    {
        $render = '';
        foreach ($currentRequestAllUrls as $urlArray) {
            $render .= '<link rel="alternate" href="' . $urlArray['url'] . '" hreflang="' . $urlArray['language']->getExternalCode() . '"/>' . "\n";
        }
        return $render;
    }
    /**
     * @return void
     */
    protected function _checkResults(Url $url, array $profile)
    {
        $this->assertEquals($profile['results']['detectBaseUrl'], $url->detectUrlDetails());
        $this->assertEquals($profile['results']['getHost'], $url->getHost());
        $this->assertEquals($profile['results']['getPathPrefix'], $url->getPathPrefix());
        $this->assertEquals($profile['results']['getPath'], $url->getPath());
        $this->assertEquals($profile['results']['getCurrentLanguage'], $url->getCurrentLanguage());
        $this->assertEquals($profile['results']['getAllUrls'], $url->getAllUrls());
    }
}
