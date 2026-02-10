<?php

namespace weglot\craftweglot\services;

use craft\base\Component;
use craft\helpers\Json;
use craft\web\View;
use weglot\craftweglot\Plugin;
use Weglot\Vendor\Weglot\Client\Api\LanguageCollection;
use Weglot\Vendor\Weglot\Client\Api\LanguageEntry;
use Weglot\Vendor\Weglot\Client\Endpoint\LanguagesList;

class LanguageService extends Component
{
    private ?LanguageCollection $allLanguages = null;

    /**
     * @param array{english: string, ...} $a
     * @param array{english: string, ...} $b
     */
    private function compareLanguage(array $a, array $b): int
    {
        return strcmp($a['english'], $b['english']);
    }

    /**
     * @throws \Exception
     */
    private function fetchLanguagesFromApi(): LanguageCollection
    {
        $client = Plugin::getInstance()->getParserService()->getClient();
        $languagesApi = new LanguagesList($client);
        $languagesResult = $languagesApi->handle();

        $languagesArray = $languagesResult->jsonSerialize();
        usort($languagesArray, $this->compareLanguage(...));

        $languageCollection = new LanguageCollection();
        foreach ($languagesArray as $language) {
            $entry = new LanguageEntry(
                $language['internal_code'],
                $language['external_code'],
                $language['english'],
                $language['local'],
                $language['rtl']
            );
            $languageCollection->addOne($entry);
        }

        return $languageCollection;
    }

    /**
     * @throws \Exception
     */
    public function getAllLanguages(): LanguageCollection
    {
        if ($this->allLanguages instanceof LanguageCollection) {
            return $this->allLanguages;
        }

        $this->allLanguages = $this->fetchLanguagesFromApi();

        $originalLanguageCode = Plugin::getInstance()->getOption()->getOption('language_from');
        $originalLanguageNameCustom = $this->getOriginalLanguageNameCustom();
        $originalLanguage = \is_string($originalLanguageCode) ? $this->allLanguages->getCode($originalLanguageCode) : null;

        if (null !== $originalLanguage && (null !== $originalLanguageNameCustom && '' !== $originalLanguageNameCustom && '0' !== $originalLanguageNameCustom)) {
            $this->allLanguages->addOne(new LanguageEntry(
                $originalLanguage->getInternalCode(),
                $originalLanguage->getExternalCode(),
                $originalLanguageNameCustom,
                $originalLanguageNameCustom,
                $originalLanguage->isRtl()
            ));
        }

        $destinationLanguagesRaw = Plugin::getInstance()->getOption()->getOption('destination_language') ?? [];
        foreach ($destinationLanguagesRaw as $d) {
            if (!isset($d['language_to'])) {
                continue;
            }
            $languageData = $this->allLanguages->getCode($d['language_to']);
            $externalCode = $d['custom_code'] ?? (null !== $languageData ? $languageData->getExternalCode() : $d['language_to']);
            $customName = $d['custom_name'] ?? (null !== $languageData ? $languageData->getEnglishName() : $d['language_to']);
            $customLocalName = $d['custom_local_name'] ?? $customName;
            $isRtl = (null !== $languageData) && $languageData->isRtl();

            $this->allLanguages->addOne(new LanguageEntry(
                $d['language_to'],
                $externalCode,
                $customName,
                $customLocalName,
                $isRtl
            ));
        }

        return $this->allLanguages;
    }

    /**
     * @throws \Exception
     */
    public function getLanguageFromInternal(string $internalCode): ?LanguageEntry
    {
        return $this->getAllLanguages()->getCode($internalCode);
    }

    /**
     * @throws \Exception
     */
    public function getLanguageFromExternal(string $externalCode): ?LanguageEntry
    {
        foreach ($this->getAllLanguages() as $language) {
            if ($language->getExternalCode() === $externalCode) {
                return $language;
            }
        }

        return null;
    }

    /**
     * @throws \Exception
     */
    public function getOriginalLanguage(): ?LanguageEntry
    {
        $originalLanguageCode = Plugin::getInstance()->getOption()->getOption('language_from') ?? 'en';

        return \is_string($originalLanguageCode) ? $this->getLanguageFromInternal($originalLanguageCode) : null;
    }

    public function getOriginalLanguageNameCustom(): ?string
    {
        $name = Plugin::getInstance()->getOption()->getOption('language_from_custom_name');

        return \is_string($name) ? $name : null;
    }

    /**
     * @return LanguageEntry[]
     *
     * @throws \Exception
     */
    public function getDestinationLanguages(): array
    {
        $destinationLanguages = [];
        $optionService = Plugin::getInstance()->getOption();

        $destOption = $optionService->getOption('destination_language') ?? null;
        if (\is_array($destOption) && [] !== $destOption) {
            foreach ($destOption as $langConfig) {
                $code = (string) ($langConfig['language_to'] ?? '');
                if ('' === $code) {
                    continue;
                }
                $entry = $this->getLanguageFromInternal($code);
                if ($entry instanceof LanguageEntry) {
                    $destinationLanguages[] = $entry;
                }
            }

            return $destinationLanguages;
        }

        $legacy = $optionService->getOption('languages') ?? [];
        $codes = [];

        if (\is_string($legacy)) {
            $parts = preg_split('/[|,\s]+/', $legacy, -1, \PREG_SPLIT_NO_EMPTY);
            $codes = \is_array($parts) ? $parts : [];
        } elseif (\is_array($legacy)) {
            foreach ($legacy as $item) {
                if (\is_string($item)) {
                    $parts = preg_split('/[|,\s]+/', $item, -1, \PREG_SPLIT_NO_EMPTY);
                    if (\is_array($parts)) {
                        $codes = array_merge($codes, $parts);
                    }
                } elseif (\is_array($item)) {
                    $code = (string) ($item['language_to'] ?? $item['code'] ?? '');
                    if ('' !== $code) {
                        $codes[] = $code;
                    }
                }
            }
        }

        $codes = array_values(array_unique(array_map(
            static fn (string $c) => strtolower(str_replace('_', '-', $c)),
            $codes
        )));

        foreach ($codes as $code) {
            $entry = $this->getLanguageFromInternal($code) ?? $this->getLanguageFromExternal($code);
            if ($entry instanceof LanguageEntry) {
                $destinationLanguages[] = $entry;
            }
        }

        return $destinationLanguages;
    }

    /**
     * @param LanguageEntry[] $entries
     *
     * @return string[]
     */
    public function codesFromDestinationEntries(array $entries, bool $excludeSource = true): array
    {
        $codes = [];

        foreach ($entries as $entry) {
            $code = $entry->getExternalCode();
            if ('' === $code) {
                $code = $entry->getInternalCode();
            }

            if ('' === $code) {
                continue;
            }

            $code = str_replace('_', '-', $code);
            if (preg_match('/^[a-z]{2}-[a-zA-Z]{2}$/', $code)) {
                [$a, $b] = explode('-', $code, 2);
                $code = strtolower($a).'-'.strtoupper($b);
            } else {
                $code = strtolower($code);
            }

            $codes[] = $code;
        }

        if ($excludeSource) {
            $source = Plugin::getInstance()->getOption()->getOption('language_from');
            if (\is_string($source) && '' !== $source) {
                $source = str_replace('_', '-', strtolower($source));
                $codes = array_filter($codes, static fn (string $l): bool => strtolower($l) !== $source);
            }
        }

        return array_values(array_unique($codes));
    }

    public function injectLanguagesJsonScript(): void
    {
        $list = [];
        try {
            foreach ($this->getAllLanguages() as $entry) {
                $list[] = [
                    'internal_code' => $entry->getInternalCode(),
                    'external_code' => $entry->getExternalCode(),
                    'english' => $entry->getEnglishName(),
                    'local' => $entry->getLocalName(),
                    'rtl' => $entry->isRtl(),
                ];
            }
        } catch (\Throwable) {
            $list = [];
        }

        $limit = 0;
        try {
            $val = Plugin::getInstance()->getOption()->getLanguagesLimit();
            if (\is_int($val) && $val > 0) {
                $limit = $val;
            }
        } catch (\Throwable) {
        }

        $payload = [
            'languages' => $list,
            'limit' => $limit,
        ];

        $json = Json::htmlEncode($payload);
        $html = '<script type="application/json" id="weglot-list-languages">'.$json.'</script>';
        \Craft::$app->getView()->registerHtml($html, View::POS_HEAD);
    }
}
