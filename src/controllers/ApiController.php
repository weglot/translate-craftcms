<?php

namespace weglot\craftweglot\controllers;

use Craft;
use craft\web\Controller;
use weglot\craftweglot\Plugin;
use yii\web\Response;

class ApiController extends Controller
{
    /**
     * @param \yii\base\Action $action
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
        $apiKey = Craft::$app->getRequest()->getRequiredBodyParam('apiKey');
        $response = Plugin::getInstance()->getUserApi()->getUserInfo($apiKey);

        return $this->asJson($response);
    }
}
