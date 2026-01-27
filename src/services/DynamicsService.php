<?php

namespace weglot\craftweglot\services;

use weglot\craftweglot\events\RegisterSelectorsEvent;
use weglot\craftweglot\Plugin;

final class DynamicsService
{
    public function addDynamics(): void
    {
        $request = \Craft::$app->getRequest();
        if (!$request->getIsSiteRequest() || $request->getIsAjax()) {
            return;
        }

        $settings = Plugin::getInstance()->getTypedSettings();
        if (false === (bool) $settings->enableDynamics) {
            return;
        }

        if (!$this->isAllowedUrl((string) ($settings->dynamicsAllowedUrls ?? ''))) {
            return;
        }

        $defaultWhitelist = [
        ];
        $defaultDynamics = $defaultWhitelist;

        $whitelist = $this->mergeSelectors(
            $defaultWhitelist,
            $this->parseSelectorsInput((string) ($settings->dynamicsWhitelistSelectors ?? ''))
        );

        $dynamics = $this->mergeSelectors(
            $defaultDynamics,
            $this->parseSelectorsInput((string) ($settings->dynamicsSelectors ?? ''))
        );

        // Extension “type apply_filters”
        $whitelistEvent = new RegisterSelectorsEvent($whitelist);
        Plugin::getInstance()->trigger(Plugin::EVENT_REGISTER_WHITELIST_SELECTORS, $whitelistEvent);
        $whitelist = $whitelistEvent->selectors;

        $dynamicsEvent = new RegisterSelectorsEvent($dynamics);
        Plugin::getInstance()->trigger(Plugin::EVENT_REGISTER_DYNAMICS_SELECTORS, $dynamicsEvent);
        $dynamics = $dynamicsEvent->selectors;

        Plugin::getInstance()->getFrontEndScripts()->injectWeglotScripts([
            'whitelist' => $whitelist,
            'dynamics' => $dynamics,
        ]);
    }

    /**
     * @return array<int, array{value: string}>
     */
    private function parseSelectorsInput(string $raw): array
    {
        $raw = trim($raw);
        if ('' === $raw) {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (\is_array($decoded)) {
            $out = [];
            foreach ($decoded as $item) {
                if (\is_string($item)) {
                    $s = trim($item);
                    if ('' !== $s) {
                        $out[] = ['value' => $s];
                    }
                    continue;
                }
                if (\is_array($item) && isset($item['value']) && \is_string($item['value'])) {
                    $s = trim($item['value']);
                    if ('' !== $s) {
                        $out[] = ['value' => $s];
                    }
                }
            }

            return $this->dedupeSelectors($out);
        }

        $parts = preg_split('/[\r\n,]+/', $raw) ?: [];
        $out = [];
        foreach ($parts as $p) {
            $s = trim($p);
            if ('' !== $s) {
                $out[] = ['value' => $s];
            }
        }

        return $this->dedupeSelectors($out);
    }

    /**
     * @param array<int, array{value: string}> $base
     * @param array<int, array{value: string}> $extra
     *
     * @return array<int, array{value: string}>
     */
    private function mergeSelectors(array $base, array $extra): array
    {
        return $this->dedupeSelectors(array_merge($base, $extra));
    }

    /**
     * @param array<int, array{value: string}> $selectors
     *
     * @return array<int, array{value: string}>
     */
    private function dedupeSelectors(array $selectors): array
    {
        $seen = [];
        $out = [];

        foreach ($selectors as $row) {
            $v = isset($row['value']) && \is_string($row['value']) ? trim($row['value']) : '';
            if ('' === $v) {
                continue;
            }
            if (isset($seen[$v])) {
                continue;
            }
            $seen[$v] = true;
            $out[] = ['value' => $v];
        }

        return $out;
    }

    /**
     * @param string $rawAllowed the raw string input representing allowed URLs
     *
     * @return bool returns true if the current URL is considered allowed based on the input, false otherwise
     */
    private function isAllowedUrl(string $rawAllowed): bool
    {
        $rawAllowed = trim($rawAllowed);
        if ('' === $rawAllowed) {
            return true; // vide => toutes les pages
        }

        if ('all' === strtolower($rawAllowed)) {
            return true;
        }

        $current = \Craft::$app->getRequest()->getAbsoluteUrl();
        if (!\is_string($current) || '' === $current) {
            return false;
        }

        $decoded = json_decode($rawAllowed, true);
        $urls = [];

        if (\is_array($decoded)) {
            foreach ($decoded as $u) {
                if (\is_string($u) && '' !== trim($u)) {
                    $urls[] = trim($u);
                }
            }
        } else {
            $parts = preg_split('/[\r\n,]+/', $rawAllowed) ?: [];
            foreach ($parts as $p) {
                $u = trim($p);
                if ('' !== $u) {
                    $urls[] = $u;
                }
            }
        }

        return \in_array($current, $urls, true);
    }
}
