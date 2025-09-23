<?php

namespace weglot\craftweglot\services;

use Craft;
use craft\base\Component;
use craft\helpers\Json;
use craft\web\View;
use Exception;
use GuzzleHttp\Exception\RequestException;
use weglot\craftweglot\helpers\HelperApi;
use weglot\craftweglot\helpers\HelperFlagType;
use weglot\craftweglot\Plugin;
use Weglot\Util\Regex;
use Weglot\Util\Regex\RegexEnum;

class OptionService extends Component
{
    /**
     * @var string|null|array<string, mixed>
     */
    protected $optionsCdn;

    /**
     * @var array<string, mixed>|null
     */
    protected $optionsFromApi;

    /**
     * @var array<string, mixed>|null Cache pour les options récupérées.
     */
    private ?array $_options = null;


    public const NO_OPTIONS = 'OPTIONS_NOT_FOUND';

    /**
     * @var array<string,mixed>
     */
    protected $optionsDefault = [
        'api_key_private' => '',
        'api_key' => '',
        'language_from' => 'en',
        'languages' => [],
        'auto_switch' => false,
        'auto_switch_fallback' => null,
        'excluded_blocks' => [],
        'excluded_paths' => [],
        'custom_css' => '',
        'switchers' => [],
        'custom_settings' => [
            'translate_email' => false,
            'translate_amp' => false,
            'translate_search' => false,
            'button_style' => [
                'full_name' => true,
                'with_name' => true,
                'is_dropdown' => true,
                'with_flags' => true,
                'flag_type' => HelperFlagType::RECTANGLE_MAT,
                'custom_css' => '',
            ],
            'switchers' => [],
            'rtl_ltr_style' => '',
            'active_wc_reload' => true,
            'flag_css' => '',
        ],
        'media_enabled' => false,
        'external_enabled' => false,
        'page_views_enabled' => false,
        'allowed' => true,
        'has_first_settings' => true,
        'show_box_first_settings' => false,
        'version' => 1,
        'translation_engine' => 2,
    ];

    /**
     * @var array<string,mixed>
     */
    protected $optionsBddDefault = [
        'has_first_settings' => true,
        'show_box_first_settings' => false,
        'menu_switcher' => [],
        'custom_urls' => [],
        'flag_css' => '',
        'active_wc_reload' => true,
    ];

    /**
     *
     * @return array<string,mixed>
     */
    public function getOptionsDefault(): array
    {
        return $this->optionsDefault;
    }

    /**
     * @return array<string,mixed>
     */
    public function getOptionsFromCdnWithApiKey(string $apiKey): array
    {
        if ($this->optionsCdn === self::NO_OPTIONS) {
            return [ 'success' => false ];
        }
        if ($this->optionsCdn) {
            return [
                'success' => true,
                'result' => $this->optionsCdn,
            ];
        }

        $cachedOptions = Craft::$app->getCache()->get('weglot_cache_cdn');
        if ($cachedOptions) {
            $this->optionsCdn = $cachedOptions;
            if ($this->optionsCdn === self::NO_OPTIONS) {
                return [ 'success' => false ];
            }

            return [
                'success' => true,
                'result' => $this->optionsCdn,
            ];
        }

        $key = str_replace('wg_', '', $apiKey);
        $url = sprintf('%s%s.json', HelperApi::getCdnUrl(), $key);
        $client = Craft::createGuzzleClient();

        try {
            $response = $client->request('GET', $url, [ 'timeout' => 3 ]);
            $statusCode = $response->getStatusCode();

            if ($statusCode === 403) {
                Craft::$app->getCache()->set('weglot_cache_cdn', self::NO_OPTIONS, 0);
                $this->optionsCdn = self::NO_OPTIONS;

                return [ 'success' => false ];
            }

            $body = json_decode((string) $response->getBody()->getContents(), true);
            Craft::$app->getCache()->set('weglot_cache_cdn', $body, 300);

            $this->optionsCdn = $body;

            return [
                'success' => true,
                'result' => $body,
            ];
        } catch (RequestException) {
            $fallbackResponse = $this->getOptionsFromApiWithApiKey($this->getApiKeyPrivate()); // Assurez-vous que getApiKeyPrivate() existe

            return [
                'success' => $fallbackResponse['success'],
                'result' => $fallbackResponse['result'],
            ];
        }
    }

    /**
     *
     *
     * @return array{success: bool, result: array<string, mixed>}
     */
    public function getOptionsFromApiWithApiKey(string $apiKey): array
    {
        if ($this->optionsFromApi) {
            return [
                'success' => true,
                'result' => $this->optionsFromApi,
            ];
        }

        $url = sprintf('%s/projects/settings?api_key=%s', HelperApi::getApiUrl(), $apiKey);
        $client = Craft::createGuzzleClient();

        try {
            $response = $client->request('GET', $url, [ 'timeout' => 3 ]);
            $body = json_decode((string) $response->getBody()->getContents(), true);

            if (null === $body || !is_array($body)) {
                return [
                    'success' => false,
                    'result' => $this->getOptionsDefault(),
                ];
            }

            $options = array_merge($this->optionsBddDefault, $body);
            $options['api_key_private'] = $this->getApiKeyPrivate(); // Cette méthode doit être implémentée

            $this->optionsFromApi = $options;

            Craft::$app->getCache()->set('weglot_cache_cdn', $options, 300);

            return [
                'success' => true,
                'result' => $options,
            ];
        } catch (Exception $e) {
            Craft::error('Erreur lors de la récupération des options Weglot depuis l\'API : ' . $e->getMessage(), __METHOD__);

            return [
                'success' => false,
                'result' => $this->getOptionsDefault(),
            ];
        }
    }

    /**
     *
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        if ($this->_options !== null) {
            return $this->_options;
        }

        $settings = Plugin::getInstance()->getTypedSettings();
        $apiKey = $settings->apiKey;

        if (!$apiKey) {
            $this->_options = $this->getOptionsDefault();

            return $this->_options;
        }

        $response = $this->getOptionsFromApiWithApiKey($apiKey);

        if ($response['success']) {
            $this->_options = array_merge($this->getOptionsDefault(), $response['result']);
        } else {
            $this->_options = $this->getOptionsDefault();
        }

        return $this->_options;
    }

    /**
     *
     *
     * @return mixed|null
     */
    public function getOption(string $key)
    {
        $options = $this->getOptions();

        return $options[ $key ] ?? null;
    }


    public function getVersion(): string
    {
        $options = $this->getOptions();

        return (string) ($options['versions']['translation'] ?? 1);
    }


    public function getTranslationEngine(): ?int
    {
        return (int) ($this->getOption('translation_engine') ?? 3);
    }


    private function getApiKeyPrivate(): string
    {
        // TODO: Implémenter la logique pour récupérer la clé API privée
        return '';
    }

    /**
     * @return array<int,string>
     * @throws Exception
     */
    public function getExcludeBlocks(): array
    {
        $rawExcludeBlocks = $this->getOption('excluded_blocks') ?? [];
        $excludeBlocks = [];

        if (is_array($rawExcludeBlocks)) {
            foreach ($rawExcludeBlocks as $block) {
                if (is_array($block) && isset($block['value'])) {
                    $excludeBlocks[] = $block['value'];
                } elseif (is_string($block)) {
                    $excludeBlocks[] = $block;
                }
            }
        }

        $excludeBlocks[] = '.menu-item-weglot a';

        $excludeBlocks[] = '.material-icons';
        $excludeBlocks[] = '.fas';
        $excludeBlocks[] = '.far';
        $excludeBlocks[] = '.fad';
        $excludeBlocks[] = '#yii-debug-toolbar';

        // TODO: Remplacer `apply_filters` par un événement Craft pour permettre l'extensibilité.

        return array_values(array_unique($excludeBlocks));
    }

    /**
     * @return array<int,mixed>
     * @throws Exception
     */
    public function getExcludeUrls(): array
    {
        $languageService = Plugin::getInstance()->getLanguage();

        $listExcludeUrls = $this->getOption('excluded_paths') ?? [];
        $excludeUrls = [];

        if (!empty($listExcludeUrls) && is_array($listExcludeUrls)) {
            foreach ($listExcludeUrls as $item) {
                if (is_array($item) && isset($item['value'], $item['type'])) {
                    $excludedLanguages = null;
                    if (!empty($item['excluded_languages']) && is_array($item['excluded_languages'])) {
                        $destinationLanguages = $languageService->getDestinationLanguages();
                        foreach ($item['excluded_languages'] as $excludedLanguageCode) {
                            foreach ($destinationLanguages as $langEntry) {
                                if ($langEntry->getInternalCode() === $excludedLanguageCode) {
                                    $excludedLanguages[] = $langEntry;
                                    break;
                                }
                            }
                        }
                    }

                    $regex = new Regex($item['type'], $item['value']);

                    $excludeUrls[] = [
                        $regex,
                        $excludedLanguages,
                        $item['exclusion_behavior'] ?? null,
                        $item['language_button_displayed'] ?? null,
                    ];
                }
            }
        }

        $cpTrigger = Craft::$app->getConfig()->getGeneral()->cpTrigger;

        if ($cpTrigger) {
            $excludeUrls[] = [ new Regex(RegexEnum::START_WITH, '/' . $cpTrigger), null ];
        }

        $excludeUrls[] = [ new Regex(RegexEnum::START_WITH, '/actions/'), null ];
        $excludeUrls[] = [ new Regex(RegexEnum::START_WITH, '/index.php/actions/'), null ];


        $excludeUrls[] = [ new Regex(RegexEnum::IS_EXACTLY, '/sitemap.xml'), null ];
        $excludeUrls[] = [ new Regex(RegexEnum::IS_EXACTLY, '/sitemap.xsl'), null ];


        // TODO: Remplacer par un événement Craft pour permettre une extensibilité tierce.

        return $excludeUrls;
    }


    public function generateWeglotData(): void
    {
        $pluginSettings = Plugin::getInstance()->getTypedSettings();
        if (empty($pluginSettings->apiKey)) {
            return;
        }

        $cache = Craft::$app->getCache();
        $settings = $cache->get('weglot_cache_cdn');

        if (!is_array($settings) || $settings === []) {
            $settings = $this->getOptions();
        }

        if (!is_array($settings)) {
            $settings = [];
        } else {
            $toUnset = [
                'deleted_at',
                'api_key',
                'api_key_private',
                'technology_id',
                'category',
                'versions',
                'wp_user_version',
                'page_views_enabled',
                'external_enabled',
                'media_enabled',
                'translate_amp',
                'translate_search',
                'translate_email',
                'button_style',
                'translation_engine',
                'auto_switch_fallback',
                'auto_switch',
                'dynamics',
                'technology_name',
            ];
            foreach ($toUnset as $k) {
                if (array_key_exists($k, $settings)) {
                    unset($settings[ $k ]);
                }
                if (isset($settings['custom_settings'][ $k ])) {
                    unset($settings['custom_settings'][ $k ]);
                }
            }
        }

        try {
            $currentLanguage = Plugin::getInstance()->getRequestUrlService()->getCurrentLanguage();
            if ($currentLanguage) {
                $settings['current_language'] = $currentLanguage->getInternalCode();
            }
        } catch (\Throwable) {
        }

        $settings['switcher_links'] = [];
        try {
            $languageService = Plugin::getInstance()->getLanguage();
            $requestUrl = Plugin::getInstance()->getRequestUrlService()->getWeglotUrl();
            $original = $languageService->getOriginalLanguage();
            $destinations = $languageService->getDestinationLanguages();
            $autoRedirect = (bool) ($this->getOption('auto_redirect') ?? $this->getOption('auto_switch') ?? false);

            $allLanguages = [];
            if ($original) {
                $allLanguages[] = $original;
            }
            foreach ($destinations as $lang) {
                $allLanguages[] = $lang;
            }

            foreach ($allLanguages as $lang) {
                $link = $requestUrl->getForLanguage($lang, true);
                if (!empty($link)) {
                    if ($autoRedirect && $original) {
                        $isOrig = ($lang->getInternalCode() === $original->getInternalCode()) ? 'true' : 'false';
                        if (is_string($link) && str_contains($link, '?')) {
                            $link = str_replace('?', "?wg-choose-original=$isOrig&", $link);
                        } else {
                            $link .= "?wg-choose-original=$isOrig";
                        }
                    }
                    $settings['switcher_links'][ $lang->getInternalCode() ] = $link;
                }
            }

            $settings['original_path'] = $requestUrl->getPath();
        } catch (\Throwable) {
        }

        if (!isset($settings['custom_settings']) || !is_array($settings['custom_settings'])) {
            $settings['custom_settings'] = [];
        }
        if (empty($settings['custom_settings']['switchers'])) {
            $settings['custom_settings']['switchers'] = [
                [
                    'name' => 'default',
                ],
            ];
        }

        $json = Json::htmlEncode($settings);
        $html = '<script type="application/json" id="weglot-data">' . $json . '</script>';
        Craft::$app->getView()->registerHtml($html, View::POS_HEAD);
    }

    /**
     *
     * @param array<string>|string $destinationLanguages
     *
     * @return array{success:bool, result?:array<string,mixed>, code?:string, message?:string}
     */
    public function saveWeglotSettings(string $publicApiKey, string $languageFrom, array|string $destinationLanguages): array
    {
        $cdn = $this->getOptionsFromCdnWithApiKey($publicApiKey);

        if (!($cdn['success'] ?? false) || empty($cdn['result']) || !is_array($cdn['result'])) {
            return [
                'success' => false,
                'code' => 'cdn_fetch_fail',
                'message' => 'Impossible de récupérer les options depuis le CDN Weglot.',
            ];
        }

        $options = array_replace_recursive($this->getOptionsDefault(), $cdn['result']);
        $apiKeyPrivate = (string) ($options['api_key'] ?? '');
        if ($apiKeyPrivate === '') {
            return [
                'success' => false,
                'code' => 'missing_private_key',
                'message' => 'Clé privée Weglot introuvable dans les options.',
            ];
        }

        $destinationLanguages = is_array($destinationLanguages) ? $destinationLanguages : [ $destinationLanguages ];

        $codes = [];
        foreach ($destinationLanguages as $item) {
            if (!is_string($item)) {
                continue;
            }
            foreach (preg_split('/[|,]/', $item) as $part) {
                $part = trim($part);
                if ($part !== '') {
                    $codes[] = $part;
                }
            }
        }
        $codes = array_values(array_unique($codes));

        $options['language_from'] = $languageFrom;
        $options['languages'] = array_map(
            static fn(string $code): array => [ 'language_to' => $code ],
            $codes
        );

        $jsonBody = json_encode($options, JSON_UNESCAPED_UNICODE);


        if ($jsonBody === false) {
            return [
                'success' => false,
                'code' => 'json_encode_fail',
                'message' => 'Echec de l’encodage JSON des options.',
            ];
        }

        $url = sprintf('%s/projects/settings?api_key=%s', \weglot\craftweglot\helpers\HelperApi::getApiUrl(), $publicApiKey);

        $client = \Craft::createGuzzleClient();

        try {
            $response = $client->request('POST', $url, [
                'timeout' => 60,
                'headers' => [
                    'technology' => 'craft',
                    'Content-Type' => 'application/json; charset=utf-8',
                ],
                'body' => $jsonBody,
            ]);

            $status = $response->getStatusCode();
            $body = (string) $response->getBody();
            $decoded = json_decode($body, true);

            if ($status >= 400) {
                return [
                    'success' => false,
                    'code' => 'api_save_http_error',
                    'message' => "Erreur HTTP $status lors de la sauvegarde Weglot.",
                ];
            }

            \Craft::$app->getCache()->set('weglot_cache_cdn', $options, 300);

            $this->optionsCdn = $options;
            $this->optionsFromApi = null;
            $this->_options = null;

            return [
                'success' => true,
                'result' => is_array($decoded) ? $decoded : [ 'raw' => $body ],
            ];
        } catch (\Throwable $e) {
            \Craft::error('Erreur lors de la sauvegarde Weglot: ' . $e->getMessage(), __METHOD__);

            return [
                'success' => false,
                'code' => 'api_save_exception',
                'message' => 'Exception lors de la sauvegarde des options Weglot.',
            ];
        }
    }

    public function getLanguagesLimit(?string $apiKey = null): ?int
    {
        try {
            if ($apiKey === null || $apiKey === '') {
                $settings = Plugin::getInstance()->getTypedSettings();
                $apiKey = (string) ($settings->apiKey ?? '');
            }
            if ($apiKey === '') {
                return null;
            }

            $cacheKey = 'weglot_languages_limit_' . substr(sha1($apiKey), 0, 16);
            $cached = Craft::$app->getCache()->get($cacheKey);
            if (is_int($cached)) {
                return $cached;
            }

            $resp = Plugin::getInstance()->getUserApi()->getUserInfo($apiKey);
            if (!is_array($resp)) {
                return null;
            }

            if (isset($resp['succeeded']) && (int) $resp['succeeded'] !== 1) {
                return null;
            }
            $payload = $resp['answer'] ?? $resp;

            $limitRaw = $payload['languages_limit'] ?? null;
            if ($limitRaw === null) {
                return null;
            }

            $limit = (int) $limitRaw;
            if ($limit < 0) {
                $limit = 0;
            }

            Craft::$app->getCache()->set($cacheKey, $limit, 300); // 5 minutes

            return $limit;
        } catch (\Throwable $e) {
            Craft::error('Erreur lors de getLanguagesLimit: ' . $e->getMessage(), __METHOD__);

            return null;
        }
    }
}
