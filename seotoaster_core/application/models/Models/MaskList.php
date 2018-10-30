<?php

/**
 *
 */
class Application_Model_Models_MaskList extends Application_Model_Models_Abstract
{

    const MASK_TYPE_MOBILE = 'mobile';

    const MASK_TYPE_DESKTOP = 'desktop';


    protected $_countryCode = '';

    protected $_maskType = self::MASK_TYPE_MOBILE;

    protected $_maskValue = '';

    protected $_fullMaskValue = '';

    /**
     * @return string
     */
    public function getCountryCode()
    {
        return $this->_countryCode;
    }

    /**
     * @param string $countryCode
     * @return string
     */
    public function setCountryCode($countryCode)
    {
        $this->_countryCode = $countryCode;

        return $this;
    }

    /**
     * @return string
     */
    public function getMaskType()
    {
        return $this->_maskType;
    }

    /**
     * @param string $maskType
     * @return string
     */
    public function setMaskType($maskType)
    {
        $this->_maskType = $maskType;

        return $this;
    }

    /**
     * @return string
     */
    public function getMaskValue()
    {
        return $this->_maskValue;
    }

    /**
     * @param string $maskValue
     * @return string
     */
    public function setMaskValue($maskValue)
    {
        $this->_maskValue = $maskValue;

        return $this;
    }

    /**
     * @return string
     */
    public function getFullMaskValue()
    {
        return $this->_fullMaskValue;
    }

    /**
     * @param string $fullMaskValue
     * @return string
     */
    public function setFullMaskValue($fullMaskValue)
    {
        $this->_fullMaskValue = $fullMaskValue;

        return $this;
    }


}