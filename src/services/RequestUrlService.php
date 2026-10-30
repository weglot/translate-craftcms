<?php

declare(strict_types=1);

namespace weglot\craftweglot\services;

use craft\base\Component;
use weglot\craftweglot\Plugin;
use Weglot\Vendor\Weglot\Client\Api\LanguageEntry;
use Weglot\Vendor\Weglot\Util\Url;

class RequestUrlService extends Component
{
    private ?Url $weglotUrl = null;

    private ?string $sourcePath = null;

    public function getFullUrl(bool $useForwardedHost = false): string
    {
        return \Craft::$app->getRequest()->getAbsoluteUrl();
    }

    /**
     * @throws \Exception
     */
    public function getWeglotUrl(): Url
    {
        if (!$this->weglotUrl instanceof Url) {
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
     * @return array<int, LanguageEntry>
     */
    public function isEligibleUrl(string $url): array
    {
        return $this->createUrlObject($url)->availableInLanguages(false);
    }

    /**
     * Resolve the canonical source-language path for the current request.
     *
     * The Weglot Url object only strips the language prefix, so on a translated
     * page its path is the translated slug. When the current language is not the
     * source one, the slug is reverse-mapped back to its source form.
     */
    public function getSourcePath(): string
    {
        if (null !== $this->sourcePath) {
            return $this->sourcePath;
        }

        $currentPath = $this->getWeglotUrl()->getPath();
        if (!\is_string($currentPath) || '' === $currentPath) {
            $currentPath = '/';
        }

        $original = Plugin::getInstance()->getLanguage()->getOriginalLanguage();
        $currentLanguage = $this->getCurrentLanguage();
        $apiKey = trim(Plugin::getInstance()->getTypedSettings()->apiKey);

        if (
            '' === $apiKey
            || !$original instanceof LanguageEntry
            || !$currentLanguage instanceof LanguageEntry
            || $currentLanguage->getInternalCode() === $original->getInternalCode()
        ) {
            return $this->sourcePath = $currentPath;
        }

        $fromExternal = strtolower(trim($currentLanguage->getExternalCode()));
        $internalPath = ltrim($currentPath, '/');
        if ('' === $fromExternal || '' === $internalPath) {
            return $this->sourcePath = $currentPath;
        }

        $rewritten = Plugin::getInstance()->getSlug()->getInternalPathIfTranslatedSlug(
            $apiKey,
            [$fromExternal],
            $fromExternal,
            $internalPath
        );

        if (null === $rewritten || '' === $rewritten) {
            return $this->sourcePath = $currentPath;
        }

        return $this->sourcePath = '/'.ltrim($rewritten, '/');
    }

    /**
     * Forward-translate the segments of a source path into the given language.
     * Falls back to the untranslated source path when no slug mapping exists.
     */
    public function getPathForLanguage(string $sourcePath, LanguageEntry $language): string
    {
        $apiKey = trim(Plugin::getInstance()->getTypedSettings()->apiKey);
        $externalCode = strtolower(trim($language->getExternalCode()));
        if ('' === $apiKey || '' === $externalCode) {
            return $sourcePath;
        }

        $internalPath = ltrim($sourcePath, '/');
        if ('' === $internalPath) {
            return $sourcePath;
        }

        $translated = Plugin::getInstance()->getSlug()->getRedirectPathIfUntranslated(
            $apiKey,
            [$externalCode],
            $externalCode,
            $internalPath
        );

        if (null === $translated || '' === $translated) {
            return $sourcePath;
        }

        return '/'.ltrim($translated, '/');
    }

    /**
     * The current page's URL in the source language, as a source-language request would carry it.
     *
     * Built from the Url object rather than the request: while a translated page renders, the
     * request is a WeglotVirtualRequest. The Url object decodes its path, so each segment is
     * encoded again; a raw '+' in a source slug comes back as '%20'.
     */
    public function getSourceUrl(): string
    {
        $weglotUrl = $this->getWeglotUrl();
        if (!$this->isOnTranslatedPage()) {
            return $weglotUrl->getUrl();
        }

        $path = implode('/', array_map(rawurlencode(...), explode('/', $this->getSourcePath())));
        $url = $weglotUrl->getHost().$path;

        $query = $weglotUrl->getQuery();
        if (null !== $query && '' !== $query) {
            $url .= '?'.$query;
        }

        return $url;
    }

    /**
     * The request_url sent with the page to translate. Weglot records it as a source page, so a
     * translated page must send its source-language URL, and an error page the shared '/404' the
     * parser would send if Craft had already set the status code. '' keeps the parser's default.
     */
    public function getTranslationRequestUrl(int $statusCode): string
    {
        if (200 !== $statusCode) {
            return $this->getWeglotUrl()->getHost().'/404';
        }

        if (!$this->isOnTranslatedPage()) {
            return '';
        }

        return $this->getSourceUrl();
    }

    private function isOnTranslatedPage(): bool
    {
        $original = Plugin::getInstance()->getLanguage()->getOriginalLanguage();
        $currentLanguage = $this->getCurrentLanguage();

        return $original instanceof LanguageEntry
            && $currentLanguage instanceof LanguageEntry
            && $currentLanguage->getInternalCode() !== $original->getInternalCode();
    }
}
