<?php

namespace Weglot\Parser\Formatter;

use Weglot\Parser\Parser;
use WGSimpleHtmlDom\simple_html_dom;

class ExcludeBlocksFormatter
{
    /**
     * @var simple_html_dom
     */
    protected $dom;

    /**
     * @var array
     */
    protected $excludeBlocks;

    /**
     * @var array
     */
    protected $whiteList;
    /**
     * @var array
     */
    protected $translateInsideExclusionsBlocks;

    /**
     * @param simple_html_dom $dom
     * @param array           $excludeBlocks
     * @param array           $whiteList
     * @param array           $translateInsideExclusionsBlocks
     */
    public function __construct($dom, $excludeBlocks, $whiteList = [], $translateInsideExclusionsBlocks = [])
    {
        $this
            ->setDom($dom)
            ->setExcludeBlocks($excludeBlocks)
            ->setWhiteList($whiteList)
            ->setTranslateInsideExclusionsBlocks($translateInsideExclusionsBlocks);
        $this->handle();
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
     * @return $this
     */
    public function setExcludeBlocks(array $excludeBlocks)
    {
        $this->excludeBlocks = $excludeBlocks;

        return $this;
    }

    /**
     * @return array
     */
    public function getExcludeBlocks()
    {
        return $this->excludeBlocks;
    }

    /**
     * @return $this
     */
    public function setWhiteList(array $whiteList)
    {
        $this->whiteList = $whiteList;

        return $this;
    }

    /**
     * @return array
     */
    public function getWhiteList()
    {
        return $this->whiteList;
    }

    /**
     * @return array
     */
    public function getTranslateInsideExclusionsBlocks()
    {
        return $this->translateInsideExclusionsBlocks;
    }

    /**
     * @return $this
     */
    public function setTranslateInsideExclusionsBlocks(array $translateInsideExclusionsBlocks)
    {
        $this->translateInsideExclusionsBlocks = $translateInsideExclusionsBlocks;

        return $this;
    }

    /**
     * Add ATTRIBUTE_NO_TRANSLATE to dom elements that don't
     * want to be translated or ATTRIBUTE_TRANSLATE if on mode
     * wg-mode-whitelist.
     *
     * @return void
     */
    public function handle()
    {
        if (!empty($this->whiteList)) {
            foreach ($this->whiteList as $exception) {
                foreach ($this->dom->find($exception) as $k => $row) {
                    $attribute = Parser::ATTRIBUTE_TRANSLATE;
                    $row->$attribute = '';
                }
            }
        } else {
            foreach ($this->excludeBlocks as $exception) {
                foreach ($this->dom->find($exception) as $k => $row) {
                    $attribute = Parser::ATTRIBUTE_NO_TRANSLATE;
                    $row->$attribute = '';
                }
            }

            foreach ($this->translateInsideExclusionsBlocks as $exception) {
                foreach ($this->dom->find($exception) as $k => $row) {
                    $attribute = Parser::ATTRIBUTE_TRANSLATE_INSIDE_BLOCKS;
                    $row->$attribute = 'true';
                }
            }
        }
    }
}
