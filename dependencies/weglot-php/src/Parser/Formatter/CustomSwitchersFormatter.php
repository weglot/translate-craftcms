<?php

namespace Weglot\Parser\Formatter;

use WGSimpleHtmlDom\simple_html_dom;

class CustomSwitchersFormatter
{
    /**
     * @var simple_html_dom
     */
    protected $dom;

    /**
     * @var array
     */
    protected $customSwitchers;

    /**
     * @param simple_html_dom $dom
     * @param array           $customSwitchers
     */
    public function __construct($dom, $customSwitchers)
    {
        $this
            ->setDom($dom)
            ->setCustomSwitchers($customSwitchers);
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
     * @return $this
     */
    public function setCustomSwitchers(array $customSwitchers)
    {
        $this->customSwitchers = $customSwitchers;

        return $this;
    }

    /**
     * @return array
     */
    public function getCustomSwitchers()
    {
        return $this->customSwitchers;
    }

    /**
     * <div class="target">target</div> foreach customswitchers
     * wanna be translated.
     *
     * @param simple_html_dom $dom
     * @param array           $switchers
     *
     * @return simple_html_dom
     */
    public function handle($dom, $switchers)
    {
        $temp_switcher = '';
        foreach ($switchers as $switcher) {
            $location = !empty($switcher['location']) ? $switcher['location'] : '';
            if (!empty($location) && !isset($switcher['template'])) {
                // we check if we find the target location
                $targets = $dom->find($location['target']);
                if ($targets) {
                    foreach ($targets as $target) {
                        // for each target we check if we have an associate sibling or we put the switcher into him
                        if (empty($location['sibling'])) {
                            $target->innertext .= '<div data-wg-position="'.$location['target'].'"></div>';
                        } else {
                            // we try to find the sibling
                            $siblings = $dom->find($location['target'].' '.$location['sibling']);
                            if ($siblings) {
                                // we check if the sibling is a parent of the target location and we put the switche before
                                foreach ($siblings as $sibling) {
                                    if (\is_object($sibling)) {
                                        $sibling->outertext = '<div data-wg-position="'.$location['target'].' '.$location['sibling'].'"></div>'.$sibling->outertext;
                                    }
                                }
                            }
                        }
                    }
                } else {
                    if (!empty($location['sibling'])) {
                        $temp_switcher .= '<div data-wg-position="'.$location['target'].$location['sibling'].'" data-wg-ajax="true"></div>';
                    } else {
                        $temp_switcher .= '<div data-wg-position="'.$location['target'].'" data-wg-ajax="true"></div>';
                    }
                }
            }
        }

        // if we have temporary switcher, we place it before the body end tag.
        if (!empty($temp_switcher)) {
            // Find the </body> tag
            $bodyTag = $dom->find('body', 0);
            // Check if $bodyTag is not null before proceeding
            if (null !== $bodyTag) {
                // Insert the new element before the </body> tag
                $bodyTag->innertext .= $temp_switcher;
            } else {
                // If $bodyTag is null, use str_replace as a fallback
                $dom = str_replace('</body>', $temp_switcher.'</body>', $dom);
            }
        }

        return $dom;
    }
}
