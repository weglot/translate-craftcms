<?php

namespace weglot\craftweglot;

use craft\base\Model;
use craft\base\Plugin as BasePlugin;
use craft\events\RegisterUrlRulesEvent;
use craft\events\TemplateEvent;
use craft\web\Request;
use craft\web\UrlManager;
use craft\web\View;
use Weglot\Client\Api\LanguageEntry;
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
use yii\web\Cookie;
use yii\web\NotFoundHttpException;

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
     *     components: array<string, array{'class': class-string}>
     * }
     */
    public static function config(): array
    {
        return [
            'components' => [
                'domCheckersService' => ['class' => DomCheckersService::class],
                'frontEndScripts' => ['class' => FrontEndScriptsService::class],
                'userApi' => ['class' => UserApiService::class],
                'option' => ['class' => OptionService::class],
                'language' => ['class' => LanguageService::class],
                'translateService' => ['class' => TranslateService::class],
                'parserService' => ['class' => ParserService::class],
                'regexCheckersService' => ['class' => RegexCheckersService::class],
                'requestUrlService' => ['class' => RequestUrlService::class],
                'replaceUrlService' => ['class' => ReplaceUrlService::class],
                'replaceLinkService' => ['class' => ReplaceLinkService::class],
                'hrefLangService' => ['class' => HrefLangService::class],
                'dashboardHelper' => ['class' => DashboardHelper::class],
            ],
        ];
    }

    /**
     * Initializes the plugin by setting up autoloading, aliases, and event handlers.
     */
    public function init(): void
    {
        parent::init();
        \Craft::setAlias('@weglot/craftweglot', $this->getBasePath());
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
                if (preg_match('/[|,\s]/', $item)) {
                    $splitResult = preg_split('/[|,\s]+/', $item, -1, \PREG_SPLIT_NO_EMPTY);
                    $parts = false !== $splitResult ? $splitResult : [];
                    foreach ($parts as $p) {
                        $normalized[] = strtolower(trim($p));
                    }
                } elseif ('' !== $item) {
                    $normalized[] = strtolower(trim($item));
                }
            }
            $languages = array_values(array_unique(array_filter($normalized)));

            if ('' === $apiKey) {
                return;
            }

            $result = self::getInstance()->getOption()->saveWeglotSettings(
                $apiKey,
                $languageFrom,
                $languages
            );

            $success = (true === $result['success']);

            if ($success) {
                if (false === $settings->hasFirstSettings) {
                    $settings->hasFirstSettings = true;
                    $settings->showBoxFirstSettings = false;

                    \Craft::$app->getPlugins()->savePluginSettings($this, $settings->toArray());

                    \Craft::$app->getSession()->set('weglot_show_first_settings_popup', true);
                }
                \Craft::$app->getSession()->setNotice(\Craft::t('weglot', 'Paramètres Weglot synchronisés avec succès.'));
            } else {
                $code = $result['code'] ?? 'unknown';
                \Craft::$app->getSession()->setError(\Craft::t('weglot', 'Échec de la synchronisation Weglot ({code}).', ['code' => $code]));
            }
        } catch (\Throwable $e) {
            \Craft::error('Synchronisation Weglot après sauvegarde: '.$e->getMessage(), __METHOD__);
            \Craft::$app->getSession()->setError(\Craft::t('weglot', 'Erreur lors de la synchronisation Weglot.'));
        }
    }

    /**
     * Attaches event handlers for handling virtual requests, URL rules, and specific rendering behaviors.
     */
    private function attachEventHandlers(): void
    {
        $request = \Craft::$app->getRequest();

        Event::on(
            View::class,
            View::EVENT_BEFORE_RENDER_PAGE_TEMPLATE,
            function (TemplateEvent $event) {
                $app = \Craft::$app;
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
                if (null === $first) {
                    return;
                }

                $entries = Plugin::getInstance()->getLanguage()->getDestinationLanguages();
                $langs = Plugin::getInstance()->getLanguage()->codesFromDestinationEntries($entries, true);
                $langs = array_map('strtolower', $langs);
                if ([] === $langs || !\in_array($first, $langs, true)) {
                    return;
                }
                $internalPath = implode('/', \array_slice($parts, 1));

                $this->weglotOriginalRequest = $req;
                $virtual = new WeglotVirtualRequest($internalPath, $req);
                $app->set('request', $virtual);
            }
        );

        Event::on(
            View::class,
            View::EVENT_AFTER_RENDER_PAGE_TEMPLATE,
            function (TemplateEvent $event) {
                if ($this->weglotOriginalRequest instanceof Request) {
                    \Craft::$app->set('request', $this->weglotOriginalRequest);
                    $this->weglotOriginalRequest = null;
                }
            }
        );

        if ($request->getIsSiteRequest()) {
            Event::on(
                UrlManager::class,
                UrlManager::EVENT_REGISTER_SITE_URL_RULES,
                function (RegisterUrlRulesEvent $e) {
                    $entries = Plugin::getInstance()->getLanguage()->getDestinationLanguages();
                    $langs = Plugin::getInstance()->getLanguage()->codesFromDestinationEntries($entries, true);

                    if ([] === $langs) {
                        return;
                    }
                    $group = strtolower(implode('|', array_map(
                        static fn (string $l): string => preg_quote($l, '#'),
                        $langs
                    )));
                    $e->rules["<lang:($group)>/actions/<action:.+>"] = 'actions/<action>';
                    $e->rules["<lang:($group)>/index.php/actions/<action:.+>"] = 'actions/<action>';

                    $e->rules["<lang:($group)>"] = 'weglot/router/forward';
                    $e->rules["<lang:($group)>/<rest:.+>"] = 'weglot/router/forward';
                }
            );
        }

        if (\Craft::$app->getRequest()->getIsCpRequest() || \Craft::$app->getRequest()->getIsConsoleRequest()) {
            return;
        }

        Event::on(
            View::class,
            View::EVENT_BEGIN_PAGE, // ou View::EVENT_HEAD si nécessaire
            function () {
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
     * @param TemplateEvent $event the event object containing the output to be processed
     */
    public function weglotInit(TemplateEvent $event): void
    {
        $languageFrom = $this->getOption()->getOption('language_from');
        if (!\is_string($languageFrom) || '' === $languageFrom || !isset($event->output)) {
            return;
        }

        $originalLanguage = self::getInstance()->getLanguage()->getOriginalLanguage();
        $currentLanguage = self::getInstance()->getRequestUrlService()->getCurrentLanguage();

        if (null === $originalLanguage || null === $currentLanguage) {
            return;
        }

        if (self::getInstance()->getRequestUrlService()->isAllowedPrivate() && !isset($_COOKIE['weglot_allow_private'])) {
            $cookie = new Cookie([
                'name' => 'weglot_allow_private',
                'value' => 'true',
                'expire' => time() + 86400 * 2,
                'path' => '/',
            ]);
            \Craft::$app->getResponse()->getCookies()->add($cookie);
        }
        $this->checkRedirect();
        $event->output = $this->getTranslateService()->processResponse($event->output);
    }

    private function checkRedirect(): void
    {
        // TODO: Implement automatic redirection logic here.
    }

    protected function createSettingsModel(): ?Model
    {
        return \Craft::createObject(Settings::class);
    }

    /**
     * Renders the settings HTML for the plugin configuration page.
     */
    protected function settingsHtml(): ?string
    {
        $settings = $this->getTypedSettings();
        $apiSettings = null;
        $cdnSettings = null;

        if ('' !== $settings->apiKey && '0' !== $settings->apiKey) {
            $apiSettings = self::getInstance()->getOption()->getOptionsFromApiWithApiKey($settings->apiKey);
            $cdnSettings = self::getInstance()->getOption()->getOptionsFromCdnWithApiKey($settings->apiKey);
        }

        $session = \Craft::$app->getSession();
        $showFirstSettingsPopup = $session->get('weglot_show_first_settings_popup', false);

        if ($showFirstSettingsPopup) {
            $session->remove('weglot_show_first_settings_popup');
        }

        return \Craft::$app->getView()->renderTemplate(
            'weglot/_settings',
            [
                'settings' => $settings,
                'apiSettings' => $apiSettings,
                'cdnSettings' => $cdnSettings,
                'showFirstSettingsPopup' => $showFirstSettingsPopup,
            ]
        );
    }

    /**
     * @return Settings the typed settings instance
     */
    public function getTypedSettings(): Settings
    {
        $settings = $this->getSettings();
        \assert($settings instanceof Settings);

        return $settings;
    }

    /**
     * @param LanguageEntry $currentLanguage the language entry representing the current language being processed
     *
     * @throws NotFoundHttpException if the exclusion behavior is set to 'NOT_FOUND'
     */
    public function handleExcludedUrlRedirects(LanguageEntry $currentLanguage): void
    {
        $originalLanguage = self::getInstance()->getLanguage()->getOriginalLanguage();

        if (null === $originalLanguage || $originalLanguage->getInternalCode() === $currentLanguage->getInternalCode()) {
            return;
        }

        $weglotUrl = self::getInstance()->getRequestUrlService()->getWeglotUrl();
        $redirectBehavior = $weglotUrl->getExcludeOption($currentLanguage, 'exclusion_behavior');

        if ('NOT_FOUND' === $redirectBehavior) {
            throw new NotFoundHttpException();
        }

        if (\is_string($redirectBehavior) && '' !== $redirectBehavior) {
            $isUrlExcluded = !$weglotUrl->getForLanguage($currentLanguage, false);
            if ($isUrlExcluded) {
                $originalLanguageUrl = $weglotUrl->getForLanguage($originalLanguage);
                if (\is_string($originalLanguageUrl) && '' !== $originalLanguageUrl) {
                    \Craft::$app->getResponse()->redirect($originalLanguageUrl, 301)->send();
                    exit;
                }
            }
        }
    }

    public function getLanguage(): LanguageService
    {
        return $this->get('language');
    }

    public function getRequestUrlService(): RequestUrlService
    {
        return $this->get('requestUrlService');
    }

    public function getTranslateService(): TranslateService
    {
        return $this->get('translateService');
    }

    public function getOption(): OptionService
    {
        return $this->get('option');
    }

    public function getReplaceUrlService(): ReplaceUrlService
    {
        return $this->get('replaceUrlService');
    }

    public function getReplaceLinkService(): ReplaceLinkService
    {
        return $this->get('replaceLinkService');
    }

    public function getParserService(): ParserService
    {
        return $this->get('parserService');
    }

    public function getDomCheckersService(): DomCheckersService
    {
        return $this->get('domCheckersService');
    }

    public function getRegexCheckersService(): RegexCheckersService
    {
        return $this->get('regexCheckersService');
    }

    public function getFrontEndScripts(): FrontEndScriptsService
    {
        return $this->get('frontEndScripts');
    }

    public function getUserApi(): UserApiService
    {
        return $this->get('userApi');
    }

    public function getHrefLangService(): HrefLangService
    {
        return $this->get('hrefLangService');
    }
}
