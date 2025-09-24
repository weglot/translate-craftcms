<?php

namespace weglot\craftweglot\helpers;

use Craft;
use Throwable;

class HelperSwitcher
{
	private const VERSIONS_PATH = 'switchers/versions.json';
	private const CACHE_KEY_PREFIX = 'weglot_switchers_versions_';
	private const CACHE_TTL = 86400;

	/**
	 *
	 * @return array<string,mixed>
	 *
	 */
	public static function getTemplateHash(?string $name = null): array
	{
		$url = self::getVersionsUrl();
		$cache = Craft::$app->getCache();
		$key = self::CACHE_KEY_PREFIX . md5($url);
		$data = $cache->get($key);

		if (!is_array($data)) {
			$data = self::fetchVersions($url);
			if (is_array($data)) {
				$cache->set($key, $data, self::CACHE_TTL);
			} else {
				$data = [];
			}
		}

		if ($name === null || $name === '') {
			return $data;
		}

		$templates = $data['templates'] ?? [];
		foreach ($templates as $tpl) {
			if (isset($tpl['name']) && $tpl['name'] === $name) {
				return $tpl;
			}
		}

		return [];
	}

	private static function getVersionsUrl(): string
	{
		return rtrim(HelperApi::getCdnUrl(), '/') . '/' . self::VERSIONS_PATH;
	}

	/**
	 * @return array{
	 *     templates: list<array{
	 *         name: string,
	 *         hash?: string
	 *     }>
	 * }|null
	 */
	private static function fetchVersions(string $url): ?array
	{
		try {
			$client = Craft::createGuzzleClient();
			$response = $client->request('GET', $url, [ 'timeout' => 5 ]);
			$body = json_decode($response->getBody()->getContents(), true);

			return is_array($body) ? $body : null;
		} catch (Throwable) {
			return null;
		}
	}

	/**
	 * @param array<int,array<string,mixed>>|null $switchers
	 *
	 * @return array{css:array<int,array<string,mixed>>,js:array<int,array<string,mixed>>}
	 */
	public static function buildSwitcherAssets(?array $switchers = null, bool $forceDefault = false): array
	{
		$assets = [
			'css' => [],
			'js' => [],
		];

		$assets['css'][] = [
			'handle' => 'weglot-switcher-default-css',
			'url' => rtrim(HelperApi::getCdnUrl(), '/') . '/weglot.min.css',
			'media' => 'screen',
			'version' => '8',
		];

		$templates = [];

		if (is_array($switchers) && $switchers !== []) {
			foreach ($switchers as $sw) {
				if (!isset($sw['template'])) {
					continue;
				}
				$tpl = self::normalizeTemplate($sw['template']);
				if ($tpl !== null) {
					$templates[ $tpl['name'] ] = $tpl;
				}
			}
		}

		if ($forceDefault || $templates === []) {
			$def = self::getTemplateHash('default');
			if ($def !== [] && (isset($def['name']) && $def['name'] !== '')) {
				$templates[ $def['name'] ] = [
					'name' => $def['name'],
					'hash' => $def['hash'] ?? null,
				];
			} else {
				$templates['default'] = [ 'name' => 'default', 'hash' => null ];
			}
		}

		foreach ($templates as $tpl) {
			$name = (string) $tpl['name'];
			$hash = $tpl['hash'] ?? null;

			if ($name === '') {
				continue;
			}

			$assets['js'][] = [
				'handle' => 'weglot-switcher-' . $name . '-js',
				'url' => self::makeJsUrl($name, is_string($hash) && $hash !== '' ? $hash : null),
				'inFooter' => true,
			];
		}

		return $assets;
	}

	/**
	 * @return array{name:string,hash?:string}|null
	 */
	private static function normalizeTemplate(mixed $tpl): ?array
	{
		if (is_string($tpl) && $tpl !== '') {
			$found = self::getHashAndTemplate($tpl);
			if ($found !== null && $found !== [] && (isset($found['name']) && ($found['name'] !== '' && $found['name'] !== '0'))) {
				return [
					'name' => $found['name'],
					'hash' => $found['hash'] ?? null,
				];
			}

			return [ 'name' => $tpl ];
		}

		if (is_array($tpl) && isset($tpl['name']) && (string)$tpl['name'] !== '') {
			return [
				'name' => (string) $tpl['name'],
				'hash' => isset($tpl['hash']) ? (string) $tpl['hash'] : null,
			];
		}

		return null;
	}

	private static function makeJsUrl(string $name, ?string $hash): string
	{
		$base = rtrim(HelperApi::getTplSwitchersUrl(), '/');
		if ($hash !== null) {
			return $base . '/' . $name . '.' . $hash . '.min.js';
		}

		return $base . '/' . $name . '.min.js';
	}

	/**
	 * @return array<string, string|null>|null
	 */
	private static function getHashAndTemplate(string $tpl): ?array
	{
		$tpl = trim($tpl);
		if ($tpl === '') {
			return null;
		}

		if (preg_match('/^(.+?)[#@]([A-Za-z0-9_-]+)$/', $tpl, $m)) {
			$name = trim($m[1]);
			$hash = $m[2];

			return [
				'name' => $name !== '' ? $name : $tpl,
				'hash' => $hash,
			];
		}

		return [
			'name' => $tpl,
			'hash' => null,
		];
	}
}