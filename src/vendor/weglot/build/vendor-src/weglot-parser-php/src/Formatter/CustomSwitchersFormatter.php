<?php

namespace Weglot\Vendor\Weglot\Parser\Formatter;

use Weglot\Vendor\WGSimpleHtmlDom\simple_html_dom;
class CustomSwitchersFormatter
{
    /**
     * @var simple_html_dom
     */
    protected $dom;
    /**
     * @var array<int, array<string, mixed>>
     */
    protected $customSwitchers;
    /**
     * @param simple_html_dom                  $dom
     * @param array<int, array<string, mixed>> $customSwitchers
     */
    public function __construct($dom, $customSwitchers)
    {
        $this->setDom($dom)->setCustomSwitchers($customSwitchers);
        $this->handle($this->dom, $customSwitchers);
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
     * @param array<int, array<string, mixed>> $customSwitchers
     *
     * @return $this
     */
    public function setCustomSwitchers(array $customSwitchers)
    {
        $this->customSwitchers = $customSwitchers;
        return $this;
    }
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getCustomSwitchers()
    {
        return $this->customSwitchers;
    }
    /**
     * @param simple_html_dom                  $dom
     * @param array<int, array<string, mixed>> $switchers
     *
     * @return simple_html_dom
     */
    public function handle($dom, $switchers)
    {
        $tempSwitcher = '';
        foreach ($switchers as $switcher) {
            $location = !empty($switcher['location']) ? $switcher['location'] : '';
            if (!empty($location) && !isset($switcher['template'])) {
                $targets = $dom->find($location['target']);
                if ($targets) {
                    foreach ($targets as $target) {
                        if (empty($location['sibling'])) {
                            $target->innertext .= '<div data-wg-position="' . $location['target'] . '"></div>';
                        } else {
                            $siblings = $dom->find($location['target'] . ' ' . $location['sibling']);
                            if ($siblings) {
                                foreach ($siblings as $sibling) {
                                    $sibling->outertext = '<div data-wg-position="' . $location['target'] . ' ' . $location['sibling'] . '"></div>' . $sibling->outertext;
                                }
                            }
                        }
                    }
                } else if (!empty($location['sibling'])) {
                    $tempSwitcher .= '<div data-wg-position="' . $location['target'] . $location['sibling'] . '" data-wg-ajax="true"></div>';
                } else {
                    $tempSwitcher .= '<div data-wg-position="' . $location['target'] . '" data-wg-ajax="true"></div>';
                }
            }
        }
        if (!empty($tempSwitcher)) {
            $bodyTag = $dom->find('body', 0);
            if (null !== $bodyTag) {
                $bodyTag->innertext .= $tempSwitcher;
            } else {
                $dom = str_replace('</body>', $tempSwitcher . '</body>', $dom);
            }
        }
        return $dom;
    }
}
