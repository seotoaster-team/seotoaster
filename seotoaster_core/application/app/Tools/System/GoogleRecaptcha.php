<?php

class Tools_System_GoogleRecaptcha
{

    const GOOGLE_URL = 'https://www.google.com/recaptcha/api/siteverify';
    protected $_secretKey;

    public function __construct()
    {
        $websiteConfig = Zend_Controller_Action_HelperBroker::getExistingHelper('config')->getConfig();
        $this->_secretKey = $websiteConfig['grecaptchaPrivateKey'];
    }

    protected function _getCurlData($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        $curlData = curl_exec($curl);
        curl_close($curl);
        return $curlData;
    }

    public function isValid($recaptcha)
    {

        $url = $this::GOOGLE_URL . "?secret=" . $this->_secretKey . "&response=" . $recaptcha;
        $websiteUrl = Zend_Controller_Action_HelperBroker::getStaticHelper('website')->getUrl();
        $hostName = parse_url($websiteUrl, PHP_URL_HOST);
        $res = $this->_getCurlData($url);
        $res = json_decode($res, true);
        if (is_array($res) && !empty($res['success']) && !empty($res['hostname'])) {
            return ($res['success'] && $res['hostname'] == $hostName);
        }
        return false;

    }
}