<?php

namespace Weglot\Parser\ConfigProvider;

abstract class AbstractConfigProvider implements ConfigProviderInterface
{
    /**
     * @var string|null
     */
    protected $title;

    /**
     * @var bool
     */
    protected $autoDiscoverTitle = true;

    /**
     * @var string
     */
    protected $url;

    /**
     * @var int
     */
    protected $bot;

    /**
     * @param string      $url
     * @param int         $bot
     * @param string|null $title Don't set this title if you want the Parser to parse title from DOM
     */
    public function __construct($url, $bot, $title = null)
    {
        $this
            ->setUrl($url)
            ->setBot($bot)
            ->setTitle($title);
    }

    /**
     * If we put a null value into $title, we would force
     * the auto discover for the Parser.
     *
     * @param string|null $title
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->setAutoDiscoverTitle(null === $title);
        $this->title = $title;

        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setAutoDiscoverTitle($autoDiscoverTitle)
    {
        $this->autoDiscoverTitle = $autoDiscoverTitle;

        return $this;
    }

    public function getAutoDiscoverTitle()
    {
        return $this->autoDiscoverTitle;
    }

    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function setBot($bot)
    {
        $this->bot = $bot;

        return $this;
    }

    public function getBot()
    {
        return $this->bot;
    }

    public function asArray()
    {
        $data = [
            'request_url' => $this->getUrl(),
            'bot' => $this->getBot(),
        ];

        if (!$this->getAutoDiscoverTitle()) {
            $data['title'] = $this->getTitle();
        }

        return $data;
    }
}
