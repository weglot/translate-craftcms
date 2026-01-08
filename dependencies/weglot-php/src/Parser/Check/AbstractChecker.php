<?php

namespace Weglot\Parser\Check;

use Weglot\Parser\Parser;
use WGSimpleHtmlDom\simple_html_dom;

abstract class AbstractChecker
{
    /**
     * @var Parser
     */
    protected $parser;

    /**
     * @var simple_html_dom
     */
    protected $dom;

    public function __construct(Parser $parser, simple_html_dom $dom)
    {
        $this
            ->setParser($parser)
            ->setDom($dom);
    }

    /**
     * @return $this
     */
    public function setParser(Parser $parser)
    {
        $this->parser = $parser;

        return $this;
    }

    /**
     * @return Parser
     */
    public function getParser()
    {
        return $this->parser;
    }

    /**
     * @return $this
     */
    public function setDom(simple_html_dom $dom)
    {
        $this->dom = $dom;

        return $this;
    }

    /**
     * @return simple_html_dom
     */
    public function getDom()
    {
        return $this->dom;
    }

    /**
     * @return mixed
     */
    abstract public function handle();
}
