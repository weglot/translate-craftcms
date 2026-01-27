<?php

namespace weglot\craftweglot\models;

use craft\base\Model;
use weglot\craftweglot\Plugin;

class Settings extends Model
{
    public string $apiKey = '';
    public string $languageFrom = 'en';
    public bool $hasFirstSettings = false;
    public bool $showBoxFirstSettings = true;
    public bool $enableDynamics = false;

    /**
     * JSON ou CSV/ligne.
     * Ex: [{"value":".cart"}] ou ".cart\n#checkout".
     */
    public string $dynamicsWhitelistSelectors = '';

    /**
     * JSON ou CSV/ligne.
     */
    public string $dynamicsSelectors = '';

    public string $dynamicsAllowedUrls = '';
    /**
     * @var string[]
     */
    public array $languages = [];

    /**
     * Defines validation rules for the properties of the model.
     *
     * @return array list of validation rules for model attributes
     */
    public function rules(): array
    {
        return [
            ['apiKey', 'required'],
            ['apiKey', 'validateApiKey'],
            ['languageFrom', 'string'],
            ['languages', 'each', 'rule' => ['string']],
            ['enableDynamics', 'boolean'],
            ['dynamicsWhitelistSelectors', 'string'],
            ['dynamicsSelectors', 'string'],
            ['dynamicsAllowedUrls', 'string'],
        ];
    }

    /**
     * @param string     $attribute the attribute name associated with the API key
     * @param mixed|null $params    additional parameters passed for validation (optional)
     */
    public function validateApiKey(string $attribute, mixed $params = null): void
    {
        $apiKey = trim($this->apiKey);
        if ('' === $apiKey) {
            return;
        }

        try {
            $response = Plugin::getInstance()->getUserApi()->getUserInfo($apiKey);
            $hasError = isset($response['error'])
                        || (isset($response['succeeded']) && 1 !== (int) $response['succeeded']);

            if ($hasError) {
                $message = $response['message'] ?? $response['error'] ?? \Craft::t('weglot', 'Invalid API Key.');
                $this->addError($attribute, (string) $message);
            }
        } catch (\Throwable $e) {
            \Craft::error('Erreur de validation de la clé API Weglot: '.$e->getMessage(), __METHOD__);
            $this->addError($attribute, \Craft::t('weglot', '- **Could not validate the API key at this time.**'));
        }
    }
}
