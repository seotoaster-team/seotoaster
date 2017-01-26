<?php

class Tools_System_GoogleRecaptcha
{

    const GOOGLE_URL = 'https://www.google.com/recaptcha/api/siteverify';
    protected $_secretKey;

    public function __construct()
    {
        $websiteConfig = Zend_Controller_Action_HelperBroker::getExistingHelper('config')->getConfig();
        $this->_secretKey = $websiteConfig['recaptchaPrivateKey'];
    }

    protected function _getCurlData($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_USERAGENT,
            "Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.2.16) Gecko/20110319 Firefox/3.6.16");
        $curlData = curl_exec($curl);
        curl_close($curl);
        return $curlData;
    }

    public function isValid($recaptcha)
    {

        $ip = $_SERVER['REMOTE_ADDR'];
        $url = $this::GOOGLE_URL . "?secret=" . $this->_secretKey . "&response=" . $recaptcha . "&remoteip=" . $ip;
        $res = $this->_getCurlData($url);
        $res = json_decode($res, true);
        if (is_array($res) && !empty($res['success'])) {
            return $res['success'];
        }
        return false;

    }
}