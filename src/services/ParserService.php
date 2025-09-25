<?php

namespace weglot\craftweglot\services;

use craft\base\Component;
use Exception;
use Weglot\Client\Client;
use weglot\craftweglot\helpers\HelperApi;
use weglot\craftweglot\Plugin;
use Weglot\Parser\Check\Dom\ExternalLinkHref;
use Weglot\Parser\Check\Dom\ImageDataSource;
use Weglot\Parser\Check\Dom\ImageSource;
use Weglot\Parser\ConfigProvider\ServerConfigProvider;
use Weglot\Parser\Parser;

class ParserService extends Component
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private readonly OptionService $optionService,
        private readonly DomCheckersService $domCheckersService,
        private readonly RegexCheckersService $regexCheckersService,

        array $config = [],
    ) {
        parent::__construct($config);
    }

    /**
     *
     * @throws Exception
     */
    public function getClient(): Client
    {
        $settings = Plugin::getInstance()->getTypedSettings();

        $version = Plugin::getInstance()->getOption()->getVersion();
        $translationEngine = Plugin::getInstance()->getOption()->getTranslationEngine();
        $apiKey = $settings->apiKey;

        return new Client(
            $apiKey,
            $translationEngine,
            $version,
            [
                'host' => HelperApi::getApiUrl(),
            ]
        );
    }

    /**
     *
     * @throws Exception
     */
    public function getParser(): Parser
    {
        $excludeBlocks = Plugin::getInstance()->getOption()->getExcludeBlocks();
        $customSwitchers = Plugin::getInstance()->getOption()->getOption('switchers');

        $config = new ServerConfigProvider();
        $config->loadFromServer();

        $client = $this->getClient();
        $safeCustomSwitchers = is_array($customSwitchers) ? $customSwitchers : [];

        $parser = new Parser($client, $config, $excludeBlocks, $safeCustomSwitchers, [], []);

        $parser->getDomCheckerProvider()->addCheckers($this->domCheckersService->getDomCheckers());
        $parser->getRegexCheckerProvider()->addCheckers($this->regexCheckersService->getRegexCheckers());

        // TODO: Remplacer par un événement Craft pour permettre la modification des noeuds ignorés
        $ignoredNodes = $parser->getIgnoredNodesFormatter()->getIgnoredNodes();
        $parser->getIgnoredNodesFormatter()->setIgnoredNodes($ignoredNodes);

        $mediaEnabled = $this->optionService->getOption('media_enabled');
        $externalEnabled = $this->optionService->getOption('external_enabled');

        $removeChecker = [];
        if (!(bool)$externalEnabled) {
            $removeChecker[] = ExternalLinkHref::class;
        }

        if (!(bool)$mediaEnabled) {
            $removeChecker[] = ImageDataSource::class;
            $removeChecker[] = ImageSource::class;
        }

        if ($removeChecker !== []) {
            $parser->getDomCheckerProvider()->removeCheckers($removeChecker);
        }

        return $parser;
    }
}
