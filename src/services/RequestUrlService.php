<?php

namespace weglot\craftweglot\services;

use Craft;
use craft\base\Component;
use Exception;
use Weglot\Client\Api\LanguageEntry;
use weglot\craftweglot\Plugin;
use Weglot\Util\Url;

class RequestUrlService extends Component
{
    private ?Url $weglotUrl = null;
    
    public function getFullUrl(bool $useForwardedHost = false): string
    {
        return Craft::$app->getRequest()->getAbsoluteUrl();
    }

    /**
     *
     * @throws Exception
     */
    public function getWeglotUrl(): Url
    {
        if (!$this->weglotUrl instanceof \Weglot\Util\Url) {
            $this->weglotUrl = $this->createUrlObject($this->getFullUrl());
        }

        return $this->weglotUrl;
    }

    public function createUrlObject(string $url): Url
    {
        $originalLanguage = Plugin::getInstance()->getLanguage()->getOriginalLanguage();
        $destinationLanguages = Plugin::getInstance()->getLanguage()->getDestinationLanguages();
        $excludeUrls = Plugin::getInstance()->getOption()->getExcludeUrls();
        $customUrls = Plugin::getInstance()->getOption()->getOption('custom_urls') ?? [];

        return new Url(
            $url,
            $originalLanguage,
            $destinationLanguages,
            '',
            $excludeUrls,
            $customUrls
        );
    }

    public function getCurrentLanguage(): ?LanguageEntry
    {
        return $this->getWeglotUrl()->getCurrentLanguage();
    }
    
    public function handlePathDetectionAndRewrite(string $path): string
    {
        $weglotUrl = $this->getWeglotUrl();
        $currentLanguage = $weglotUrl->getCurrentLanguage();
        $originalLanguage = $weglotUrl->getDefault();

        if ($currentLanguage->getInternalCode() !== $originalLanguage->getInternalCode()) {
            if (trim($path, '/') === $currentLanguage->getExternalCode()) {
                return '/';
            }

            return $weglotUrl->getPath();
        }

        return $path;
    }

    public function isAllowedPrivate(): bool
    {
        return false; // TODO
    }

    /**
     * @return array<string, mixed>
     */
    public function isEligibleUrl(string $url): array
    {
        return $this->createUrlObject($url)->availableInLanguages(false);
    }
}
