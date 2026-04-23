<?php

declare(strict_types=1);

namespace Weglot\Parser\ConfigProvider;

use Weglot\Client\Api\Enum\BotType;
use Weglot\Util\Server;

class ServerConfigProvider extends AbstractConfigProvider
{
    /**
     * @param string|null $title Don't set this title if you want the Parser to parse title from DOM
     */
    public function __construct($title = null)
    {
        parent::__construct('', BotType::HUMAN, $title);
    }

    /**
     * Is used to load server data, you have to run it manually !
     *
     * @param string $canonical
     */
    public function loadFromServer($canonical = ''): void
    {
        if (!empty($canonical)) {
            $url = $canonical;
        } else {
            if (200 !== http_response_code()) {
                $url = Server::urlOrigin($_SERVER).'/404';
            } else {
                $url = Server::fullUrl($_SERVER);
            }
        }

        $this
            ->setUrl($url)
            ->setBot(Server::detectBot($_SERVER));
    }
}
