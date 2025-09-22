<?php

namespace weglot\craftweglot;

use Craft;
use craft\base\Model;
use craft\base\Plugin as BasePlugin;
use craft\events\RegisterUrlRulesEvent;
use craft\events\TemplateEvent;
use craft\web\Request;
use craft\web\UrlManager;
use craft\web\View;
use weglot\craftweglot\helpers\DashboardHelper;
use weglot\craftweglot\models\Settings;
use weglot\craftweglot\services\DomCheckersService;
use weglot\craftweglot\services\FrontEndScriptsService;
use weglot\craftweglot\services\HrefLangService;
use weglot\craftweglot\services\LanguageService;
use weglot\craftweglot\services\OptionService;
use weglot\craftweglot\services\ParserService;
use weglot\craftweglot\services\RegexCheckersService;
use weglot\craftweglot\services\ReplaceLinkService;
use weglot\craftweglot\services\ReplaceUrlService;
use weglot\craftweglot\services\RequestUrlService;
use weglot\craftweglot\services\TranslateService;
use weglot\craftweglot\services\UserApiService;
use weglot\craftweglot\web\WeglotVirtualRequest;
use yii\base\Event;
use yii\base\InvalidConfigException;

class Plugin extends BasePlugin
{
    public string $schemaVersion = '1.0.0';
    public bool $hasCpSettings = true;
    public string $icon = '@weglot/craftweglot/resources/icon.svg';

    private ?Request $weglotOriginalRequest = null;

    /**
     * Configures and returns an array of components and their respective services.
     *
     * @return array{
     *     components: array<string, class-string>
     * }
     */

    public static function config(): array
    {
        return [
            'components' => [
                'domCheckersService' => DomCheckersService::class,
                'frontEndScripts' => FrontEndScriptsService::class,
                'userApi' => UserApiService::class,
                'option' => OptionService::class,
                'language' => LanguageService::class,
                'translateService' => TranslateService::class,
                'parserService' => ParserService::class,
                'regexCheckersService' => RegexCheckersService::class,
                'requestUrlService' => RequestUrlService::class,
                'replaceUrlService' => ReplaceUrlService::class,
                'replaceLinkService' => ReplaceLinkService::class,
                'hrefLangService' => HrefLangService::class,
                'dashboardHelper' => DashboardHelper::class,
            ],
        ];
    }

    /**
     * Initializes the plugin by setting up autoloading, aliases, and event handlers.
     */
    public function init(): void
    {
        require_once $this->getBasePath() . '/../vendor/autoload.php';
        parent::init();
        Craft::setAlias('@weglot/craftweglot', $this->getBasePath());
        $this->attachEventHandlers();
    }


    /**
     * Synchronizes settings with Weglot after saving.
     */
    public function afterSaveSettings(): void
    {
        parent::afterSaveSettings();

        try {
            $settings = $this->getTypedSettings();
            $apiKey = trim($settings->apiKey);
            $languageFrom = $settings->languageFrom;
            $languages = $settings->languages;
            $normalized = [];
            foreach ($languages as $item) {
                if (is_string($item) && preg_match('/[|,\s]/', $item)) {
                    $parts = preg_split('/[|,\s]+/', $item, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                    foreach ($parts as $p) {
                        $normalized[] = strtolower(trim($p));
                    }
                } else {
                    $normalized[] = strtolower(trim((string) $item));
                }
            }
            $languages = array_values(array_unique(array_filter($normalized)));

            if ($apiKey === '') {
                return;
            }

            $result = Plugin::getInstance()->getOption()->saveWeglotSettings(
                $apiKey,
                $languageFrom,
                $languages
            );

            $success = ($result['success'] === true);

            if ($success) {
                Craft::$app->getSession()->setNotice(Craft::t('weglot', 'Paramètres Weglot synchronisés avec succès.'));
            } else {
                $code = $result['code'] ?? 'unknown';
                Craft::$app->getSession()->setError(Craft::t('weglot', 'Échec de la synchronisation Weglot ({code}).', [ 'code' => $code ]));
            }
        } catch (\Throwable $e) {
            Craft::error('Synchronisation Weglot après sauvegarde: ' . $e->getMessage(), __METHOD__);
            Craft::$app->getSession()->setError(Craft::t('weglot', 'Erreur lors de la synchronisation Weglot.'));
        }
    }

    /**
     * Attaches event handlers for handling virtual requests, URL rules, and specific rendering behaviors.
     */
    private function attachEventHandlers(): void
    {
        $request = Craft::$app->getRequest();

        Event::on(
            View::class,
            View::EVENT_BEFORE_RENDER_PAGE_TEMPLATE,
            function(TemplateEvent $event) {
                $app = Craft::$app;
                $req = $app->getRequest();

                if (!$req->getIsSiteRequest() || $req->getIsAjax()) {
                    return;
                }

                if ($req instanceof WeglotVirtualRequest) {
                    return;
                }

                $realPath = $req->getPathInfo(true);
                $parts = array_values(array_filter(explode('/', trim($realPath, '/'))));
                $first = $parts[0] ?? null;
                if (!$first) {
                    return;
                }

                $entries = Plugin::getInstance()->getLanguage()->getDestinationLanguages();
                $langs = Plugin::getInstance()->getLanguage()->codesFromDestinationEntries($entries, true);

                if (!$langs || !in_array($first, $langs, true)) {
                    return;
                }

                $internalPath = implode('/', array_slice($parts, 1));

                $this->weglotOriginalRequest = $req;
                $virtual = new WeglotVirtualRequest($internalPath, $req);
                $app->set('request', $virtual);
            }
        );

        Event::on(
            View::class,
            View::EVENT_AFTER_RENDER_PAGE_TEMPLATE,
            function(TemplateEvent $event) {
                if ($this->weglotOriginalRequest instanceof \craft\web\Request) {
                    Craft::$app->set('request', $this->weglotOriginalRequest);
                    $this->weglotOriginalRequest = null;
                }
            }
        );

        if ($request->getIsSiteRequest()) {
            Event::on(
                UrlManager::class,
                UrlManager::EVENT_REGISTER_SITE_URL_RULES,
                function(RegisterUrlRulesEvent $e) {
                    $entries = Plugin::getInstance()->getLanguage()->getDestinationLanguages();
                    $langs = Plugin::getInstance()->getLanguage()->codesFromDestinationEntries($entries, true);

                    if (!$langs) {
                        return;
                    }

                    $group = implode('|', array_map(fn($l) => preg_quote($l, '#'), $langs));
                    $e->rules["<lang:($group)>/actions/<action:.+>"] = 'actions/<action>';
                    $e->rules["<lang:($group)>/index.php/actions/<action:.+>"] = 'actions/<action>';

                    $e->rules["<lang:($group)>"] = 'weglot/router/forward';
                    $e->rules["<lang:($group)>/<rest:.+>"] = 'weglot/router/forward';
                }
            );
        }

        if (Craft::$app->getRequest()->getIsCpRequest() || Craft::$app->getRequest()->getIsConsoleRequest()) {
            return;
        }

        Event::on(
            View::class,
            View::EVENT_BEGIN_PAGE, // ou View::EVENT_HEAD si nécessaire
            function() {
                Plugin::getInstance()->getHrefLangService()->injectHrefLangTags();
                Plugin::getInstance()->getOption()->generateWeglotData();
                $this->getFrontEndScripts()->injectSwitcherAssets();
            }
        );

        Event::on(
            View::class,
            View::EVENT_AFTER_RENDER_PAGE_TEMPLATE,
            $this->weglotInit(...)
        );
    }

    /**
     * Initializes Weglot functionality during a template event.
     *
     * @param TemplateEvent $event The event object containing the output to be processed.
     */
    public function weglotInit(TemplateEvent $event): void
    {
        if (!$this->getOption()->getOption('language_from') || !isset($event->output)) {
            return;
        }

        $originalLanguage = Plugin::getInstance()->getLanguage()->getOriginalLanguage();
        $currentLanguage = Plugin::getInstance()->getRequestUrlService()->getCurrentLanguage();

        if ($originalLanguage === null || $currentLanguage === null) {
            return;
        }

        if (Plugin::getInstance()->getRequestUrlService()->isAllowedPrivate() && !isset($_COOKIE['weglot_allow_private'])) {
            $cookie = new \yii\web\Cookie([
                'name' => 'weglot_allow_private',
                'value' => 'true',
                'expire' => time() + 86400 * 2,
                'path' => '/',
            ]);
            Craft::$app->getResponse()->getCookies()->add($cookie);
        }
        $this->checkRedirect();
        $event->output = $this->getTranslateService()->processResponse($event->output);
    }

    private function checkRedirect(): void
    {
        // TODO: Implémenter la logique de redirection automatique ici.
    }


    protected function createSettingsModel(): ?Model
    {
        return Craft::createObject(Settings::class);
    }

    /**
     * Renders the settings HTML for the plugin configuration page.
     */
    protected function settingsHtml(): ?string
    {
        $settings = $this->getTypedSettings();
        $apiSettings = null;
        $cdnSettings = null;

        if ($settings->apiKey !== '' && $settings->apiKey !== '0') {
            $apiSettings = Plugin::getInstance()->getOption()->getOptionsFromApiWithApiKey($settings->apiKey);
            $cdnSettings = Plugin::getInstance()->getOption()->getOptionsFromCdnWithApiKey($settings->apiKey);
        }

        return Craft::$app->getView()->renderTemplate(
            'weglot/_settings',
            [
                'settings' => $this->getSettings(),
                'apiSettings' => $apiSettings,
                'cdnSettings' => $cdnSettings,
            ]
        );
    }


    /**
     *
     * @return Settings The typed settings instance.
     */
    public function getTypedSettings(): Settings
    {
        $settings = $this->getSettings();
        assert($settings instanceof Settings);

        return $settings;
    }

    /**
     *
     * @param \Weglot\Client\Api\LanguageEntry $currentLanguage The language entry representing the current language being processed.
     *
     * @throws \yii\web\NotFoundHttpException If the exclusion behavior is set to 'NOT_FOUND'.
     */
    public function handleExcludedUrlRedirects(\Weglot\Client\Api\LanguageEntry $currentLanguage): void
    {
        $originalLanguage = Plugin::getInstance()->getLanguage()->getOriginalLanguage();

        if (!$originalLanguage || $originalLanguage->getInternalCode() === $currentLanguage->getInternalCode()) {
            return;
        }

        $weglotUrl = Plugin::getInstance()->getRequestUrlService()->getWeglotUrl();
        $redirectBehavior = $weglotUrl->getExcludeOption($currentLanguage, 'exclusion_behavior');

        if ($redirectBehavior === 'NOT_FOUND') {
            throw new \yii\web\NotFoundHttpException();
        }

        if ($redirectBehavior) {
            $isUrlExcluded = !$weglotUrl->getForLanguage($currentLanguage, false);
            if ($isUrlExcluded) {
                $originalLanguageUrl = $weglotUrl->getForLanguage($originalLanguage);
                if ($originalLanguageUrl) {
                    Craft::$app->getResponse()->redirect($originalLanguageUrl, 301)->send();
                    exit();
                }
            }
        }
    }

    /**
     *
     * @throws InvalidConfigException
     */
    public function getLanguage(): LanguageService
    {
        /** @var LanguageService */
        return $this->get('language');
    }

    /**
     *
     * @throws InvalidConfigException
     */
    public function getRequestUrlService(): RequestUrlService
    {
        /** @var RequestUrlService */
        return $this->get('requestUrlService');
    }

    /**
     *
     * @throws InvalidConfigException
     */
    public function getTranslateService(): TranslateService
    {
        /** @var TranslateService */
        return $this->get('translateService');
    }

    /**
     *
     * @throws InvalidConfigException
     */
    public function getOption(): OptionService
    {
        /** @var OptionService */
        return $this->get('option');
    }

    /**
     *
     * @throws InvalidConfigException
     */
    public function getReplaceUrlService(): ReplaceUrlService
    {
        /** @var ReplaceUrlService */
        return $this->get('replaceUrlService');
    }

    /**
     *
     * @throws InvalidConfigException
     */
    public function getReplaceLinkService(): ReplaceLinkService
    {
        /** @var ReplaceLinkService */
        return $this->get('replaceLinkService');
    }

    /**
     *
     * @throws InvalidConfigException
     */
    public function getParserService(): ParserService
    {
        /** @var ParserService */
        return $this->get('parserService');
    }


    public function getDomCheckersService(): DomCheckersService
    {
        /** @var DomCheckersService */
        return $this->get('domCheckersService');
    }


    public function getRegexCheckersService(): RegexCheckersService
    {
        /** @var RegexCheckersService */
        return $this->get('regexCheckersService');
    }


    public function getFrontEndScripts(): FrontEndScriptsService
    {
        /** @var FrontEndScriptsService */
        return $this->get('frontEndScripts');
    }


    public function getUserApi(): UserApiService
    {
        /** @var UserApiService */
        return $this->get('userApi');
    }


    public function getHrefLangService(): HrefLangService
    {
        /** @var HrefLangService */
        return $this->get('hrefLangService');
    }
}
