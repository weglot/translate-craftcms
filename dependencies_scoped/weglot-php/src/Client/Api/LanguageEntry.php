<?php

namespace Weglot\Vendor\Weglot\Client\Api;

use Weglot\Vendor\Weglot\Client\Api\Shared\AbstractCollectionEntry;
class LanguageEntry extends AbstractCollectionEntry
{
    /**
     * Internal weglot code to identify language, is 2 letters, eg tw.
     *
     * @var string
     */
    protected $internalCode;
    /**
     * External code that shows on website on URLs (can be more than 2 letters).
     *
     * @var string
     */
    protected $externalCode;
    /**
     * English name of the language.
     *
     * @var string
     */
    protected $englishName;
    /**
     * Name of the language in the language.
     *
     * @var string
     */
    protected $localName;
    /**
     * Language is right to left.
     *
     * @var bool
     */
    protected $isRtl;
    /**
     * @param string $internalCode Internal weglot code to identify language
     * @param string $externalCode External code that shows on website on URLs
     * @param string $englishName  English name of the language
     * @param string $localName    Name of the language in the language
     * @param bool   $isRtl        Language is right to left
     */
    public function __construct($internalCode, $externalCode, $englishName, $localName, $isRtl = \false)
    {
        $this->setInternalCode($internalCode)->setExternalCode($externalCode)->setEnglishName($englishName)->setLocalName($localName)->setRtl($isRtl);
    }
    /**
     * @param string $internalCode
     *
     * @return $this
     */
    public function setInternalCode($internalCode)
    {
        $this->internalCode = $internalCode;
        return $this;
    }
    /**
     * @return string
     */
    public function getInternalCode()
    {
        return $this->internalCode;
    }
    /**
     * @param string|null $externalCode
     *
     * @return $this
     */
    public function setExternalCode($externalCode)
    {
        $this->externalCode = isset($externalCode) ? $externalCode : $this->internalCode;
        return $this;
    }
    /**
     * @return string
     */
    public function getExternalCode()
    {
        return $this->externalCode;
    }
    /**
     * @param string $englishName
     *
     * @return $this
     */
    public function setEnglishName($englishName)
    {
        $this->englishName = $englishName;
        return $this;
    }
    /**
     * @return string
     */
    public function getEnglishName()
    {
        return $this->englishName;
    }
    /**
     * @param string $localName
     *
     * @return $this
     */
    public function setLocalName($localName)
    {
        $this->localName = $localName;
        return $this;
    }
    /**
     * @return string
     */
    public function getLocalName()
    {
        return $this->localName;
    }
    /**
     * @param bool $rtl
     *
     * @return $this
     */
    public function setRtl($rtl)
    {
        $this->isRtl = $rtl;
        return $this;
    }
    /**
     * @return bool
     */
    public function isRtl()
    {
        return $this->isRtl;
    }
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return ['internal_code' => $this->getInternalCode(), 'external_code' => $this->getExternalCode(), 'english' => $this->getEnglishName(), 'local' => $this->getLocalName(), 'rtl' => $this->isRtl()];
    }
}
