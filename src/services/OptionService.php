<?php

namespace weglot\craftweglot\services;

use craft\base\Component;
use craft\helpers\Json;
use craft\web\View;
use GuzzleHttp\Exception\RequestException;
use weglot\craftweglot\helpers\HelperApi;
use weglot\craftweglot\helpers\HelperFlagType;
use weglot\craftweglot\Plugin;
use Weglot\Vendor\Weglot\Util\Regex;
use Weglot\Vendor\Weglot\Util\Regex\RegexEnum;

class OptionService extends Component
{
    /**
     * @var string|array<string, mixed>|null
     */
    protected string|array|null $optionsCdn = null;

    /**
     * @var array<string, mixed>|null
     */
    protected ?array $optionsFromApi = null;

    /**
     * @var array<string, mixed>|null
     */
    private ?array $_options = null;

    public const NO_OPTIONS = 'OPTIONS_NOT_FOUND';

    /**
     * @var int
     */
    private const PUBLIC_API_KEY_CACHE_TTL = 86400;

    /**
     * @var array<string,mixed>
     */
    protected array $optionsDefault = [
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
    protected array $optionsBddDefault = [
        'has_first_settings' => true,
        'show_box_first_settings' => false,
        'menu_switcher' => [],
        'custom_urls' => [],
        'flag_css' => '',
        'active_wc_reload' => true,
    ];

    /**
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
        if (self::NO_OPTIONS === $this->optionsCdn) {
            return ['success' => false];
        }
        if (\is_array($this->optionsCdn)) {
            return [
                'success' => true,
                'result' => $this->optionsCdn,
            ];
        }

        $cachedOptions = \Craft::$app->getCache()->get('weglot_cache_cdn');
        if (false !== $cachedOptions) {
            if (self::NO_OPTIONS === $cachedOptions) {
                $this->optionsCdn = self::NO_OPTIONS;

                return ['success' => false];
            }
            if (\is_array($cachedOptions)) {
                $this->optionsCdn = $cachedOptions;

                return [
                    'success' => true,
                    'result' => $this->optionsCdn,
                ];
            }
        }

        $key = str_replace('wg_', '', $apiKey);
        $url = \sprintf('%s%s.json', HelperApi::getCdnUrl(), $key);
        $client = \Craft::createGuzzleClient();

        try {
            $response = $client->request('GET', $url, ['timeout' => 3]);
            $statusCode = $response->getStatusCode();

            if (403 === $statusCode) {
                \Craft::$app->getCache()->set('weglot_cache_cdn', self::NO_OPTIONS, 0);
                $this->optionsCdn = self::NO_OPTIONS;

                return ['success' => false];
            }

            $body = json_decode($response->getBody()->getContents(), true);
            \Craft::$app->getCache()->set('weglot_cache_cdn', $body, 300);

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
     * @return array{success: true, result: array<string, mixed>}|array{success: false, result: array<string, mixed>}
     */
    public function getOptionsFromApiWithApiKey(string $apiKey): array
    {
        if (null !== $this->optionsFromApi) {
            return [
                'success' => true,
                'result' => $this->optionsFromApi,
            ];
        }

        $url = \sprintf('%s/projects/settings?api_key=%s', HelperApi::getApiUrl(), $apiKey);
        $client = \Craft::createGuzzleClient();

        try {
            $response = $client->request('GET', $url, ['timeout' => 3]);
            $body = json_decode($response->getBody()->getContents(), true);

            if (!\is_array($body)) {
                return [
                    'success' => false,
                    'result' => $this->getOptionsDefault(),
                ];
            }

            $options = array_merge($this->optionsBddDefault, $body);
            $options['api_key_private'] = $this->getApiKeyPrivate();

            $this->optionsFromApi = $options;

            \Craft::$app->getCache()->set('weglot_cache_cdn', $options, 300);

            return [
                'success' => true,
                'result' => $options,
            ];
        } catch (\Exception $e) {
            \Craft::error('Error retrieving Weglot options from API : '.$e->getMessage(), __METHOD__);

            return [
                'success' => false,
                'result' => $this->getOptionsDefault(),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        if (null !== $this->_options) {
            return $this->_options;
        }

        $settings = Plugin::getInstance()->getTypedSettings();
        $apiKey = $settings->apiKey;

        if ('' === $apiKey) {
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
     * @return array<mixed>|null
     */
    public function getOption(string $key): string|array|null
    {
        $options = $this->getOptions();

        return $options[$key] ?? null;
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
        // TODO: Implement the logic to retrieve the private API key
        return '';
    }

    /**
     * @return array<int,string>
     *
     * @throws \Exception
     */
    public function getExcludeBlocks(): array
    {
        $rawExcludeBlocks = $this->getOption('excluded_blocks') ?? [];
        $excludeBlocks = [];

        if (\is_array($rawExcludeBlocks)) {
            foreach ($rawExcludeBlocks as $block) {
                if (\is_array($block) && isset($block['value'])) {
                    $excludeBlocks[] = $block['value'];
                } elseif (\is_string($block)) {
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

        // TODO: Replace `apply_filters` with a Craft event to allow extensibility.

        return array_values(array_unique($excludeBlocks));
    }

    /**
     * @return array<int,mixed>
     *
     * @throws \Exception
     */
    public function getExcludeUrls(): array
    {
        $languageService = Plugin::getInstance()->getLanguage();

        $listExcludeUrls = $this->getOption('excluded_paths') ?? [];
        $excludeUrls = [];

        if (\is_array($listExcludeUrls) && [] !== $listExcludeUrls) {
            foreach ($listExcludeUrls as $item) {
                if (\is_array($item) && isset($item['value'], $item['type'])) {
                    $excludedLanguages = null;
                    if (isset($item['excluded_languages']) && \is_array($item['excluded_languages']) && [] !== $item['excluded_languages']) {
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

        $cpTrigger = \Craft::$app->getConfig()->getGeneral()->cpTrigger;

        if (null !== $cpTrigger && '' !== $cpTrigger) {
            $excludeUrls[] = [new Regex(RegexEnum::START_WITH, '/'.$cpTrigger), null];
        }

        $excludeUrls[] = [new Regex(RegexEnum::START_WITH, '/actions/'), null];
        $excludeUrls[] = [new Regex(RegexEnum::START_WITH, '/index.php/actions/'), null];

        $excludeUrls[] = [new Regex(RegexEnum::IS_EXACTLY, '/sitemap.xml'), null];
        $excludeUrls[] = [new Regex(RegexEnum::IS_EXACTLY, '/sitemap.xsl'), null];

        // TODO: Replace with a Craft event to allow third-party extensibility.

        return $excludeUrls;
    }

    /**
     * @return string The public API key. Returns an empty string if no valid API key is found.
     */
    public function getPublicApiKey(): string
    {
        $settings = Plugin::getInstance()->getTypedSettings();
        $seed = (string) ($settings->apiKey ?? '');
        $cacheKey = 'weglot_public_api_key_'.substr(sha1($seed), 0, 16);

        $cache = \Craft::$app->getCache();
        $cached = $cache->get($cacheKey);
        if (\is_string($cached) && '' !== $cached) {
            return $cached;
        }

        $value = $this->getOption('api_key');
        $publicKey = \is_string($value) ? trim($value) : '';

        if ('' !== $publicKey) {
            $cache->set($cacheKey, $publicKey, self::PUBLIC_API_KEY_CACHE_TTL);
        }

        return $publicKey;
    }

    public function generateWeglotData(): void
    {
        $pluginSettings = Plugin::getInstance()->getTypedSettings();
        if ('' === $pluginSettings->apiKey) {
            return;
        }

        $cache = \Craft::$app->getCache();
        $settings = $cache->get('weglot_cache_cdn');

        if (!\is_array($settings) || [] === $settings) {
            $settings = $this->getOptions();
        }

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
            if (\array_key_exists($k, $settings)) {
                unset($settings[$k]);
            }
            if (isset($settings['custom_settings'][$k])) {
                unset($settings['custom_settings'][$k]);
            }
        }

        try {
            $currentLanguage = Plugin::getInstance()->getRequestUrlService()->getCurrentLanguage();
            if (null !== $currentLanguage) {
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

            $currentLanguage = Plugin::getInstance()->getRequestUrlService()->getCurrentLanguage();

            $allLanguages = [];
            if (null !== $original) {
                $allLanguages[] = $original;
            }
            foreach ($destinations as $lang) {
                $allLanguages[] = $lang;
            }

            foreach ($allLanguages as $lang) {
                $link = $requestUrl->getForLanguage($lang, true);
                if ('' !== $link) {
                    try {
                        if (
                            null !== $original
                            && null !== $currentLanguage
                            && $lang->getInternalCode() === $original->getInternalCode()
                            && $currentLanguage->getInternalCode() !== $original->getInternalCode()
                        ) {
                            $settingsModel = Plugin::getInstance()->getTypedSettings();
                            $apiKey = trim((string) $settingsModel->apiKey);

                            $fromExternal = strtolower(trim((string) $currentLanguage->getExternalCode())); // ex: fr
                            if ('' !== $apiKey && '' !== $fromExternal) {
                                $parsed = parse_url($link);
                                $path = \is_array($parsed) ? ($parsed['path'] ?? '') : '';
                                if (\is_string($path) && '' !== $path) {
                                    $internalPath = ltrim($path, '/'); // ex: blog-fr
                                    $rewritten = Plugin::getInstance()->getSlug()->getInternalPathIfTranslatedSlug(
                                        $apiKey,
                                        [$fromExternal],
                                        $fromExternal,
                                        $internalPath
                                    );

                                    if (null !== $rewritten && $rewritten !== $internalPath) {
                                        $newPath = '/'.ltrim($rewritten, '/');

                                        $rebuilt = $newPath;
                                        if (\is_array($parsed) && isset($parsed['scheme'], $parsed['host'])) {
                                            $rebuilt = $parsed['scheme'].'://'.$parsed['host']
                                                       .(isset($parsed['port']) ? ':'.$parsed['port'] : '')
                                                       .$newPath
                                                       .(isset($parsed['query']) && '' !== $parsed['query'] ? '?'.$parsed['query'] : '')
                                                       .(isset($parsed['fragment']) && '' !== $parsed['fragment'] ? '#'.$parsed['fragment'] : '');
                                        } elseif (\is_array($parsed)) {
                                            $rebuilt = $newPath
                                                       .(isset($parsed['query']) && '' !== $parsed['query'] ? '?'.$parsed['query'] : '')
                                                       .(isset($parsed['fragment']) && '' !== $parsed['fragment'] ? '#'.$parsed['fragment'] : '');
                                        }

                                        $link = $rebuilt;
                                    }
                                }
                            }
                        }
                    } catch (\Throwable) {
                        // silent
                    }

                    if ($autoRedirect && null !== $original) {
                        $isOrig = ($lang->getInternalCode() === $original->getInternalCode()) ? 'true' : 'false';
                        if (str_contains($link, '?')) {
                            $link = str_replace('?', "?wg-choose-original=$isOrig&", $link);
                        } else {
                            $link .= "?wg-choose-original=$isOrig";
                        }
                    }
                    $settings['switcher_links'][$lang->getInternalCode()] = $link;
                }
            }

            $settings['original_path'] = $requestUrl->getPath();
        } catch (\Throwable) {
        }

        if (!\is_array($settings['custom_settings'] ?? null)) {
            $settings['custom_settings'] = [];
        }
        if (($settings['custom_settings']['switchers'] ?? []) === []) {
            $settings['custom_settings']['switchers'] = [
                [
                    'name' => 'default',
                ],
            ];
        }

        $json = Json::htmlEncode($settings);
        $html = '<script type="application/json" id="weglot-data">'.$json.'</script>';
        \Craft::$app->getView()->registerHtml($html, View::POS_HEAD);
    }

    /**
     * @param array<string>|string $destinationLanguages
     *
     * @return array{success:bool, result?:array<string,mixed>, code?:string, message?:string}
     */
    public function saveWeglotSettings(string $publicApiKey, string $languageFrom, array|string $destinationLanguages): array
    {
        $cdn = $this->getOptionsFromApiWithApiKey($publicApiKey);

        if (false === $cdn['success']) {
            return [
                'success' => false,
                'code' => 'cdn_fetch_fail',
                'message' => 'Unable to retrieve options from Weglot CDN.',
            ];
        }

        $options = array_replace_recursive($this->getOptionsDefault(), $cdn['result']);
        $apiKeyPrivate = $options['api_key'] ?? '';
        if ('' === $apiKeyPrivate) {
            return [
                'success' => false,
                'code' => 'missing_private_key',
                'message' => 'Weglot private key not found in options.',
            ];
        }

        $destinationLanguages = \is_array($destinationLanguages) ? $destinationLanguages : [$destinationLanguages];

        $codes = [];
        foreach ($destinationLanguages as $item) {
            foreach (preg_split('/[|,]/', $item) as $part) {
                $part = trim($part);
                if ('' !== $part) {
                    $codes[] = $part;
                }
            }
        }
        $codes = array_values(array_unique($codes));

        $options['language_from'] = $languageFrom;
        $options['languages'] = array_map(
            static fn (string $code): array => ['language_to' => $code],
            $codes
        );

        $jsonBody = json_encode($options, \JSON_UNESCAPED_UNICODE);

        if (false === $jsonBody) {
            return [
                'success' => false,
                'code' => 'json_encode_fail',
                'message' => 'Failed to JSON encode options.',
            ];
        }

        $url = \sprintf('%s/projects/settings?api_key=%s', HelperApi::getApiUrl(), $publicApiKey);

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
                    'message' => "HTTP error $status while saving Weglot.",
                ];
            }

            \Craft::$app->getCache()->set('weglot_cache_cdn', $options, 300);

            $this->optionsCdn = $options;
            $this->optionsFromApi = null;
            $this->_options = null;

            return [
                'success' => true,
                'result' => \is_array($decoded) ? $decoded : ['raw' => $body],
            ];
        } catch (\Throwable $e) {
            \Craft::error('Error while saving Weglot: '.$e->getMessage(), __METHOD__);

            return [
                'success' => false,
                'code' => 'api_save_exception',
                'message' => 'Exception while saving Weglot options.',
            ];
        }
    }

    public function getLanguagesLimit(?string $apiKey = null): ?int
    {
        try {
            $currentApiKey = $apiKey;
            if (null === $currentApiKey || '' === $currentApiKey) {
                $settings = Plugin::getInstance()->getTypedSettings();
                $currentApiKey = $settings->apiKey ?? '';
            }
            if ('' === $currentApiKey) {
                return null;
            }

            $cacheKey = 'weglot_languages_limit_'.substr(sha1($currentApiKey), 0, 16);
            $cached = \Craft::$app->getCache()->get($cacheKey);
            if (\is_int($cached)) {
                return $cached;
            }

            $resp = Plugin::getInstance()->getUserApi()->getUserInfo($currentApiKey);

            if (isset($resp['succeeded']) && 1 !== (int) $resp['succeeded']) {
                return null;
            }
            $payload = $resp['answer'] ?? $resp;

            $limitRaw = $payload['languages_limit'] ?? null;
            if (null === $limitRaw) {
                return null;
            }

            $limit = (int) $limitRaw;
            if ($limit < 0) {
                $limit = 0;
            }

            \Craft::$app->getCache()->set($cacheKey, $limit, 300);

            return $limit;
        } catch (\Throwable $e) {
            \Craft::error('Error while gettingLanguagesLimit '.$e->getMessage(), __METHOD__);

            return null;
        }
    }
}
