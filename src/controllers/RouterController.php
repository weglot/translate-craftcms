<?php

declare(strict_types=1);

namespace weglot\craftweglot\controllers;

use craft\base\Element;
use craft\elements\db\ElementQuery;
use craft\web\Controller;
use craft\web\Request;
use craft\web\Response;
use weglot\craftweglot\helpers\HelperApi;
use weglot\craftweglot\Plugin;
use weglot\craftweglot\services\SlugService;
use weglot\craftweglot\web\WeglotVirtualRequest; // AJOUT
use yii\base\Event;
use yii\web\NotFoundHttpException;

class RouterController extends Controller
{
    protected array|int|bool $allowAnonymous = ['forward'];

    private const SLUG_REDIRECT_MAX_AGE = 86400;

    /**
     * @throws NotFoundHttpException if the path starts with "actions/" or no valid route/template is resolved
     */
    public function actionForward(string $lang, string $rest = ''): mixed
    {
        if (str_starts_with($rest, 'actions/')) {
            $routeId = substr($rest, \strlen('actions/')); // ex: "debug/default/toolbar"

            return \Craft::$app->runAction($routeId);
        }
        if (str_starts_with($rest, 'index.php/actions/')) {
            $routeId = substr($rest, \strlen('index.php/actions/'));

            return \Craft::$app->runAction($routeId);
        }

        $currentLanguage = Plugin::getInstance()->getLanguage()->getLanguageFromExternal($lang);

        if (null !== $currentLanguage) {
            Plugin::getInstance()->handleExcludedUrlRedirects($currentLanguage);
        }

        try {
            if (null !== $currentLanguage) {
                $internalPath = trim($rest, '/');
                if ('' !== $internalPath) {
                    $settings = Plugin::getInstance()->getTypedSettings();
                    $apiKey = trim($settings->apiKey);

                    $destinationEntries = Plugin::getInstance()->getLanguage()->getDestinationLanguages();
                    $destinationCodes = Plugin::getInstance()->getLanguage()->codesFromDestinationEntries($destinationEntries, true);

                    if ('' !== $apiKey && [] !== $destinationCodes) {
                        $slugRedirect = self::resolveSlugRedirect(
                            Plugin::getInstance()->getSlug(),
                            $apiKey,
                            $destinationCodes,
                            $currentLanguage->getExternalCode(),
                            $internalPath,
                            self::isLinkFollow(\Craft::$app->getRequest())
                        );

                        if (null !== $slugRedirect) {
                            $response = $this->redirect(self::buildLanguageRedirectTarget(
                                $this->languageRedirectBasePath(),
                                $lang,
                                $slugRedirect['path'],
                                \Craft::$app->getRequest()->getQueryString()
                            ), 301);
                            if (null !== $slugRedirect['maxAge'] && $response instanceof Response) {
                                $response->setCacheHeaders($slugRedirect['maxAge']);
                            }

                            return $response;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            \Craft::warning('Slug canonical redirect failed: '.$e->getMessage(), __METHOD__);
        }

        $this->requireSiteRequest();

        Event::on(
            ElementQuery::class,
            ElementQuery::EVENT_INIT,
            static function (Event $event) {
                /** @var ElementQuery<int, Element> $query */
                $query = $event->sender;
                if (null === $query->siteId) {
                    $query->siteId(\Craft::$app->getSites()->getPrimarySite()->id);
                }
            }
        );

        $siteId = \Craft::$app->getSites()->getPrimarySite()->id;
        $internalPath = trim($rest, '/');

        try {
            if (null !== $currentLanguage && '' !== $internalPath) {
                $settings = Plugin::getInstance()->getTypedSettings();
                $apiKey = trim($settings->apiKey);

                $langExternal = strtolower(trim($currentLanguage->getExternalCode()));
                $destinationCodes = ('' !== $langExternal) ? [$langExternal] : [];

                if ('' !== $apiKey && [] !== $destinationCodes) {
                    $rewritten = Plugin::getInstance()->getSlug()->getInternalPathIfTranslatedSlug(
                        $apiKey,
                        $destinationCodes,
                        $langExternal,
                        $internalPath
                    );

                    if (null !== $rewritten && $rewritten !== $internalPath) {
                        $internalPath = $rewritten;
                    }
                }
            }
        } catch (\Throwable $e) {
            \Craft::warning('Slug internal rewrite failed: '.$e->getMessage(), __METHOD__);
        }

        $candidates = ('' === $internalPath) ? ['', '__home__'] : [$internalPath];

        $originalRequest = \Craft::$app->getRequest();

        // Injects the virtual request (segments based on the internal path without /fr)
        $virtualRequest = new WeglotVirtualRequest($internalPath, $originalRequest);
        \Craft::$app->set('request', $virtualRequest);

        try {
            foreach ($candidates as $uri) {
                $element = \Craft::$app->getElements()->getElementByUri($uri, $siteId, true);

                if (null !== $element) {
                    $route = $element->getRoute();
                    if (\is_array($route) && 'templates/render' === $route[0] && isset($route[1]['template']) && '' !== $route[1]['template']) {
                        return $this->renderTemplate($route[1]['template'], [$element::class::refHandle() => $element]);
                    }
                    if (null !== $route) {
                        return \is_array($route)
                            ? \Craft::$app->runAction($route[0], $route[1] ?? [])
                            : \Craft::$app->runAction($route);
                    }
                }
            }

            // Attempt dynamic project routes
            try {
                $parsedRoute = \Craft::$app->getUrlManager()->parseRequest($virtualRequest);
                if (false !== $parsedRoute) {
                    [$routeId, $params] = $parsedRoute;

                    return \Craft::$app->runAction($routeId, $params ?? []);
                }
            } catch (\Throwable) {
                // ignore
            }

            $tpl = ('' === $internalPath) ? 'index' : $internalPath;
            if (\Craft::$app->getView()->doesTemplateExist($tpl)) {
                return $this->renderTemplate($tpl);
            }

            $tplIndex = rtrim($tpl, '/').'/index';
            if (\Craft::$app->getView()->doesTemplateExist($tplIndex)) {
                return $this->renderTemplate($tplIndex);
            }

            throw new NotFoundHttpException();
        } finally {
            \Craft::$app->set('request', $originalRequest);
        }
    }

    /**
     * The original-slug redirect comes first, as in the WordPress plugin: an original slug that is
     * also a former translated slug goes to its own page. The former-slug lookup runs on the path as
     * requested, never on the path mapped back to original slugs.
     *
     * @param array<int, string> $destinationCodes
     *
     * @return array{path: string, maxAge: int|null}|null
     */
    public static function resolveSlugRedirect(SlugService $slugService, string $apiKey, array $destinationCodes, string $languageTo, string $requestedPath, bool $isLinkFollow): ?array
    {
        $translated = $slugService->getRedirectPathIfUntranslated($apiKey, $destinationCodes, $languageTo, $requestedPath);
        if (null !== $translated && $translated !== $requestedPath) {
            // Weglot 2 slugs are edited in the dashboard: an uncapped 301 would keep sending browsers
            // to a translation that has since been removed.
            return ['path' => $translated, 'maxAge' => HelperApi::isV2ApiKey($apiKey) ? self::SLUG_REDIRECT_MAX_AGE : null];
        }

        // Former slugs are links followed by visitors and crawlers; a script or a form posting to one
        // would get a redirected response it did not ask for, and a 301 turns a POST into a GET.
        if (!$isLinkFollow) {
            return null;
        }

        $current = $slugService->getRedirectPathIfFormerSlug($apiKey, $destinationCodes, $languageTo, $requestedPath);

        // Cached for a day only: a slug renamed back to a former one would otherwise loop on the
        // browser's cached 301.
        return null !== $current ? ['path' => $current, 'maxAge' => self::SLUG_REDIRECT_MAX_AGE] : null;
    }

    public static function isLinkFollow(Request $request): bool
    {
        return !$request->getIsAjax() && ($request->getIsGet() || $request->getIsHead());
    }

    /**
     * Root-relative, so Response::redirect() keeps it as is: the base path must already carry the
     * subdirectory the site is installed in, or the visitor lands outside the site.
     */
    public static function buildLanguageRedirectTarget(string $basePath, string $lang, string $path, string $query): string
    {
        // The requested segments arrive decoded while a slug from the API may already be encoded:
        // decoding first keeps an encoded slug from being encoded twice.
        $segments = array_map(
            static fn (string $segment): string => rawurlencode(rawurldecode($segment)),
            explode('/', ltrim($path, '/'))
        );
        $target = rtrim($basePath, '/').'/'.trim($lang, '/').'/'.implode('/', $segments);

        return '' !== $query ? $target.'?'.$query : $target;
    }

    /**
     * The `<lang>/<rest>` rule matches the path after the current site's base path, so a
     * path-based multisite (`/uk/`) needs the current site's, not the primary site's.
     */
    private function languageRedirectBasePath(): string
    {
        $siteBaseUrl = \Craft::$app->getSites()->getCurrentSite()->getBaseUrl();
        if (null === $siteBaseUrl) {
            return \Craft::$app->getRequest()->getBaseUrl();
        }

        $basePath = parse_url($siteBaseUrl, \PHP_URL_PATH);

        return \is_string($basePath) ? $basePath : '';
    }
}
