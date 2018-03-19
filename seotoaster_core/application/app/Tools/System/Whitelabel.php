<?php

/**
 * White-label
 *
 * Class Tools_System_Whitelabel
 */
class Tools_System_Whitelabel
{

    const REMOTE_REGISTRATION_LINK = 'https://mojo.seosamba.com/register.html';

    const REMOTE_URL = 'https://mojo.seosamba.com/';

    const REMOTE_DOCUMENTATION_URL = 'https://www.seotoaster.com/';

    const WHITE_LABEL_DOCUMENTATION_DEFAULT_DOMAIN = 'http://help.website-today.org/';


    /**
     * Is website white-labeled
     *
     * @return bool
     * @throws Zend_Controller_Action_Exception
     */
    public static function isWhiteLabel()
    {
        $whiteLabel = Zend_Controller_Action_HelperBroker::getExistingHelper('config')->getConfig('useWhiteLabel');
        if (!empty($whiteLabel)) {
            return true;
        }

        return false;

    }

    /**
     * Return white labeled documentation domain
     *
     * @return mixed|string
     * @throws Zend_Controller_Action_Exception
     */
    public static function getDocumentationWhiteLabeledDomain()
    {
        if (self::isWhiteLabel()) {
            $generalConfigHelper = Zend_Controller_Action_HelperBroker::getExistingHelper('config');
            $whiteLabelDomain = filter_var($generalConfigHelper->getConfig('whiteLabelDocumentationUrl'), FILTER_VALIDATE_URL,
                FILTER_FLAG_HOST_REQUIRED | FILTER_FLAG_SCHEME_REQUIRED);
            if (!empty($whiteLabelDomain)) {
                $whiteLabelDomain = parse_url($whiteLabelDomain, PHP_URL_SCHEME)  .'://'. parse_url($whiteLabelDomain,
                        PHP_URL_HOST) . parse_url($whiteLabelDomain,
                        PHP_URL_PATH);

                $whiteLabelDomain = rtrim($whiteLabelDomain, '/') . '/';

                return $whiteLabelDomain;

            }

            return self::WHITE_LABEL_DOCUMENTATION_DEFAULT_DOMAIN;
        }

        return self::REMOTE_DOCUMENTATION_URL;
    }


    /**
     * Return white labeled domain
     *
     * @return mixed|string
     * @throws Zend_Controller_Action_Exception
     */
    public static function getWhiteLabeledDomain()
    {
        if (self::isWhiteLabel()) {
            $generalConfigHelper = Zend_Controller_Action_HelperBroker::getExistingHelper('config');
            $whiteLabelDomain = filter_var($generalConfigHelper->getConfig('whiteLabelDomain'), FILTER_VALIDATE_URL,
                FILTER_FLAG_HOST_REQUIRED | FILTER_FLAG_SCHEME_REQUIRED);
            if (!empty($whiteLabelDomain)) {
                $whiteLabelDomain = parse_url($whiteLabelDomain, PHP_URL_SCHEME) .'://'. parse_url($whiteLabelDomain,
                        PHP_URL_HOST) . parse_url($whiteLabelDomain,
                        PHP_URL_PATH);

                $whiteLabelDomain = rtrim($whiteLabelDomain, '/') . '/';

                return $whiteLabelDomain;

            }

        }

        return self::REMOTE_URL;
    }


    /**
     * Return white labeled registration link
     *
     * @return mixed|string
     * @throws Zend_Controller_Action_Exception
     */
    public static function getWhiteLabeledRegistrationLink()
    {
        if (self::isWhiteLabel()) {
            $generalConfigHelper = Zend_Controller_Action_HelperBroker::getExistingHelper('config');
            $whiteLabelDomain = filter_var($generalConfigHelper->getConfig('whiteLabelRegisterUrl'), FILTER_VALIDATE_URL,
                FILTER_FLAG_HOST_REQUIRED | FILTER_FLAG_SCHEME_REQUIRED);
            if (!empty($whiteLabelDomain)) {
                return $whiteLabelDomain;
            }

        }

        return self::REMOTE_REGISTRATION_LINK;
    }

    /**
     * Return cms brand name
     *
     * @return string
     * @throws Zend_Controller_Action_Exception
     */
    public static function getCmsBrandName()
    {
        if (self::isWhiteLabel()) {
            $generalConfigHelper = Zend_Controller_Action_HelperBroker::getExistingHelper('config');
            $cmsBrandName = $generalConfigHelper->getConfig('whiteLabelCmsBrandName');
            if (!empty($cmsBrandName)) {
                return $cmsBrandName;
            }
        }

        return 'Seotoaster';

    }

    /**
     * Return company name
     *
     * @return string
     * @throws Zend_Controller_Action_Exception
     */
    public static function getCompanyName()
    {
        if (self::isWhiteLabel()) {
            $generalConfigHelper = Zend_Controller_Action_HelperBroker::getExistingHelper('config');
            $cmsBrandName = $generalConfigHelper->getConfig('whiteLabelCompanyName');
            if (!empty($cmsBrandName)) {
                return $cmsBrandName;
            }
        }

        return 'SeoSamba';

    }

    /**
     * Return white-labeled logo url
     *
     * @return string
     * @throws Zend_Controller_Action_Exception
     */
    public static function getWhiteLabelLogo()
    {
        if (self::isWhiteLabel()) {
            $generalConfigHelper = Zend_Controller_Action_HelperBroker::getExistingHelper('config');
            $whiteLabelLogo = $generalConfigHelper->getConfig('whiteLabelLogo');
            if (!empty($whiteLabelLogo)) {
                return $whiteLabelLogo;
            }
        }

        return '';

    }
}

