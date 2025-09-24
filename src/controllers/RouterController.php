<?php

namespace weglot\craftweglot\controllers;

use Craft;
use craft\base\Element;
use craft\elements\db\ElementQuery;
use craft\web\Controller;
use weglot\craftweglot\Plugin;
use weglot\craftweglot\web\WeglotVirtualRequest; // AJOUT
use yii\base\Event;
use yii\web\NotFoundHttpException;

class RouterController extends Controller
{
	protected array|int|bool $allowAnonymous = ['forward'];

	/**
	 * @param string $lang The language code to process the request for, extracted from the URL.
	 * @param string $rest The remaining path segment after the language code, used for routing or rendering.
	 *
	 * @return mixed The response of the routed action or rendered template. Throws exceptions if no proper route is found.
	 * @throws NotFoundHttpException If the path starts with "actions/" or no valid route/template is resolved.
	 */
	public function actionForward(string $lang, string $rest = '')
	{
		if (str_starts_with($rest, 'actions/')) {
			$routeId = substr($rest, strlen('actions/')); // ex: "debug/default/toolbar"
			return \Craft::$app->runAction($routeId);
		}
		if (str_starts_with($rest, 'index.php/actions/')) {
			$routeId = substr($rest, strlen('index.php/actions/'));
			return \Craft::$app->runAction($routeId);
		}

		$currentLanguage = Plugin::getInstance()->getLanguage()->getLanguageFromExternal($lang);
		if ($currentLanguage !== null) {
			Plugin::getInstance()->handleExcludedUrlRedirects($currentLanguage);
		}

		$this->requireSiteRequest();

		Event::on(
			ElementQuery::class,
			ElementQuery::EVENT_INIT,
			function(Event $event) {
				/** @var ElementQuery<int, Element> $query */
				$query = $event->sender;
				if ($query->siteId === null) {
					$query->siteId(Craft::$app->getSites()->getPrimarySite()->id);
				}
			}
		);

		$siteId = Craft::$app->getSites()->getPrimarySite()->id;
		$internalPath = trim($rest, '/');
		$candidates = ($internalPath === '') ? ['', '__home__'] : [$internalPath];

		$originalRequest = Craft::$app->getRequest();

		// Injecte le Request virtuel (segments basés sur le chemin interne sans /fr)
		$virtualRequest = new WeglotVirtualRequest($internalPath, $originalRequest);
		Craft::$app->set('request', $virtualRequest);

		try {
			// A) Essai "élément" (Entrée, Catégorie, etc.)
			foreach ($candidates as $uri) {
				/** @var ?Element $element */
				$element = Craft::$app->getElements()->getElementByUri($uri, $siteId, true);

				if ($element !== null) {
					$route = $element->getRoute();
					if (is_array($route) && $route[0] === 'templates/render' && isset($route[1]['template']) && $route[1]['template'] !== '') {
						return $this->renderTemplate($route[1]['template'], [get_class($element)::refHandle() => $element]);
					}
					if ($route !== null) {
						return is_array($route)
							? Craft::$app->runAction($route[0], $route[1] ?? [])
							: Craft::$app->runAction($route);
					}
				}
			}

			// B) Essai routes dynamiques du projet
			try {
				$parsedRoute = Craft::$app->getUrlManager()->parseRequest($virtualRequest);
				if ($parsedRoute !== false) {
					[$routeId, $params] = $parsedRoute;
					return Craft::$app->runAction($routeId, $params ?? []);
				}
			} catch (\Throwable) {
				// ignore
			}

			$tpl = ($internalPath === '') ? 'index' : $internalPath;
			if (Craft::$app->getView()->doesTemplateExist($tpl)) {
				return $this->renderTemplate($tpl);
			}

			throw new NotFoundHttpException();
		} finally {
			Craft::$app->set('request', $originalRequest);
		}
	}
}