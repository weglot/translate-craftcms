<?php

declare(strict_types=1);

namespace weglot\craftweglot\controllers;

use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\services\ProjectConfig;
use craft\web\Controller;
use weglot\craftweglot\Plugin;
use yii\base\Action;
use yii\web\Response;

class ApiController extends Controller
{
    /**
     * @param Action $action
     *
     * @phpstan-ignore-next-line typeCoverage.paramTypeCoverage
     */
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $this->requireAdmin();
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        return true;
    }

    public function actionValidateApiKey(): Response
    {
        $apiKey = \Craft::$app->getRequest()->getRequiredBodyParam('apiKey');
        $response = Plugin::getInstance()->getUserApi()->getUserInfo($apiKey);

        return $this->asJson($response);
    }

    public function actionResetSettings(): Response
    {
        $plugin = Plugin::getInstance();

        $plugin->getOption()->resetOptions();

        // savePluginSettings() validates Settings::rules() which rejects an empty apiKey.
        // We write directly to project config (same storage Craft uses internally) to bypass validation.
        $configPath = ProjectConfig::PATH_PLUGINS.'.'.$plugin->handle.'.settings';
        $resetSettings = ProjectConfigHelper::packAssociativeArrays([
            'apiKey' => '',
            'languageFrom' => 'en',
            'languages' => [],
            'hasFirstSettings' => false,
            'showBoxFirstSettings' => true,
            'enableDynamics' => false,
            'enableAlgolia' => false,
            'dynamicsWhitelistSelectors' => '',
            'dynamicsAllowedUrls' => '',
        ]);
        \Craft::$app->getProjectConfig()->set($configPath, $resetSettings, 'Reset Weglot plugin settings');

        return $this->asJson(['success' => true]);
    }
}
