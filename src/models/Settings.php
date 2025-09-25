<?php

namespace weglot\craftweglot\models;

use craft\base\Model;
use weglot\craftweglot\Plugin;

/**
 * Weglot settings.
 */
class Settings extends Model
{
    public string $apiKey = '';

    public string $languageFrom = 'en';

    /**
     * @var string[]
     */
    public array $languages = [];

    /**
     * @return array<int, mixed>
     */
    public function rules(): array
    {
        return [
            ['apiKey', 'required'],
            ['apiKey', 'validateApiKey'],
            ['languageFrom', 'string'],
            ['languages', 'each', 'rule' => ['string']],
        ];
    }

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
