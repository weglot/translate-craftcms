<?php

namespace weglot\craftweglot\controllers;

use craft\web\Controller;
use weglot\craftweglot\Plugin;
use yii\base\Action;
use yii\web\Response;

class ApiController extends Controller
{
	/** @phpstan-param \yii\base\Action $action */
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
}
