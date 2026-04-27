<?php

namespace Weglot\Vendor\Weglot\Parser\Formatter;

use Weglot\Vendor\Weglot\Parser\Parser;
use Weglot\Vendor\WGSimpleHtmlDom\simple_html_dom;
class ExcludeBlocksFormatter
{
    /**
     * @var simple_html_dom
     */
    protected $dom;
    /**
     * @var array<string>
     */
    protected $excludeBlocks;
    /**
     * @var array<string>
     */
    protected $whiteList;
    /**
     * @var array<string>
     */
    protected $translateInsideExclusionsBlocks;
    /**
     * @param simple_html_dom $dom
     * @param array<string>   $excludeBlocks
     * @param array<string>   $whiteList
     * @param array<string>   $translateInsideExclusionsBlocks
     */
    public function __construct($dom, $excludeBlocks, $whiteList = [], $translateInsideExclusionsBlocks = [])
    {
        $this->setDom($dom)->setExcludeBlocks($excludeBlocks)->setWhiteList($whiteList)->setTranslateInsideExclusionsBlocks($translateInsideExclusionsBlocks);
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
     * @param array<string> $excludeBlocks
     *
     * @return $this
     */
    public function setExcludeBlocks(array $excludeBlocks)
    {
        $this->excludeBlocks = $excludeBlocks;
        return $this;
    }
    /**
     * @return array<string>
     */
    public function getExcludeBlocks()
    {
        return $this->excludeBlocks;
    }
    /**
     * @param array<string> $whiteList
     *
     * @return $this
     */
    public function setWhiteList(array $whiteList)
    {
        $this->whiteList = $whiteList;
        return $this;
    }
    /**
     * @return array<string>
     */
    public function getWhiteList()
    {
        return $this->whiteList;
    }
    /**
     * @return array<string>
     */
    public function getTranslateInsideExclusionsBlocks()
    {
        return $this->translateInsideExclusionsBlocks;
    }
    /**
     * @param array<string> $translateInsideExclusionsBlocks
     *
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
     */
    public function handle(): void
    {
        if (!empty($this->whiteList)) {
            foreach ($this->whiteList as $exception) {
                foreach ($this->dom->find($exception) as $k => $row) {
                    $attribute = Parser::ATTRIBUTE_TRANSLATE;
                    $row->{$attribute} = '';
                }
            }
        } else {
            foreach ($this->excludeBlocks as $exception) {
                foreach ($this->dom->find($exception) as $k => $row) {
                    $attribute = Parser::ATTRIBUTE_NO_TRANSLATE;
                    $row->{$attribute} = '';
                }
            }
            foreach ($this->translateInsideExclusionsBlocks as $exception) {
                foreach ($this->dom->find($exception) as $k => $row) {
                    $attribute = Parser::ATTRIBUTE_TRANSLATE_INSIDE_BLOCKS;
                    $row->{$attribute} = 'true';
                }
            }
        }
    }
}
