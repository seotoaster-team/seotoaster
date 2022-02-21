<?php

class Tools_System_FormBlacklist
{
    /**
     * Check whether params are blacklisted
     *
     * @param string $email email address
     * @param array $params array of params
     * @return bool
     */
    public static function isBlacklisted($email, $params = array())
    {
        if (self::isBlacklistedEmail($email)) {
            return true;
        }

        if (empty($ipAddress)) {
            $ipAddress = Tools_System_Tools::getIpAddress();
        }

        if (self::isBlacklistedIpAddress($ipAddress)) {
            return true;
        }

        $emailParts = explode('@', $email);
        if (self::isBlacklistedDomain('@'.$emailParts[1])) {
            return true;
        }

        $splitIpAddress = explode('.', $ipAddress);
        if (!empty($splitIpAddress)) {
            $ipCpartAddressMask = $splitIpAddress[0] . '.' . $splitIpAddress[1] . '.' . $splitIpAddress[2] . '.*';
            if (self::isBlacklistedIpAddressCpart($ipCpartAddressMask)) {
                return true;
            }
        }


        if (!empty($params)) {
            return self::isBlacklistedHtmlTags($params);
        }

        return false;

    }

    /**
     * Check whether email is blacklisted
     *
     * @param string $email email address
     * @return bool
     */
    public static function isBlacklistedEmail($email)
    {
        $formBlacklistRulesMapper = Application_Model_Mappers_FormBlacklistRulesMapper::getInstance();
        $result = $formBlacklistRulesMapper->getByEmailType($email);
        if (empty($result)) {
            return false;
        }

        return true;

    }

    /**
     * Check whether domain is blacklisted
     *
     * @param string $domain domain Ex: @gmail.com
     * @return bool
     */
    public static function isBlacklistedDomain($domain)
    {
        $formBlacklistRulesMapper = Application_Model_Mappers_FormBlacklistRulesMapper::getInstance();
        $result = $formBlacklistRulesMapper->getByDomainType($domain);
        if (empty($result)) {
            return false;
        }

        return true;


    }

    /**
     * Check whether domain is blacklisted
     *
     * @param string $ipAddress domain Ex: 5.188.84.*
     * @return bool
     */
    public static function isBlacklistedIpAddressCpart($ipAddress)
    {
        $formBlacklistRulesMapper = Application_Model_Mappers_FormBlacklistRulesMapper::getInstance();
        $result = $formBlacklistRulesMapper->getByIpPartc($ipAddress);
        if (empty($result)) {
            return false;
        }

        return true;


    }

    /**
     * Check whether ip address is blacklisted
     *
     * @param string $ipAddress ip address
     * @return bool
     */
    public static function isBlacklistedIpAddress($ipAddress)
    {
        $formBlacklistRulesMapper = Application_Model_Mappers_FormBlacklistRulesMapper::getInstance();
        $result = $formBlacklistRulesMapper->getByIpAddressType($ipAddress);
        if (empty($result)) {
            return false;
        }

        return true;

    }

    /**
     * Check whether html tags are blacklisted
     * @param array $params array of params
     * @return bool
     */
    public static function isBlacklistedHtmlTags($params)
    {
        $formBlacklistRulesMapper = Application_Model_Mappers_FormBlacklistRulesMapper::getInstance();
        $result = $formBlacklistRulesMapper->getByHtmlTags();
        if (empty($result)) {
            return false;
        }

        if (empty($params)) {
            return false;
        }

        if (empty($result[0]['value'])) {
            return false;
        }

        foreach ($params as $param) {
            if ($param != strip_tags($param)) {
                return true;
            }
        }

        return false;
    }

    public static function isSpam($params = array())
    {
        if (!empty($params)) {
            $createdAt = Tools_System_Tools::convertDateFromTimezone('now');
            $ip = self::getIpAddress();
            $formName = $params['formName'];
            $cleanedFormParams = self::cleanFormData($params);
            $filteredFormParams = $cleanedFormParams;
            $filteredFormParams['ip'] = $ip;
            $filteredFormParams['createdAt'] = $createdAt;
            $filteredFormParams['formName'] = $formName;
            $response = Apps::apiCall('POST', 'appsValidateLeadFormData', array(), array(
                'data' => array(
                    'formParams' => $filteredFormParams
                )
            ), 1);

            if (empty($response)) {
                return false;
            }

            if (!empty($response['isContentSpam'])) {
                return true;
            }
         }

        return false;
    }

    public static function getIpAddress()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }

    /**
     * Clean form data fields
     *
     * @param array $data form data
     * @return mixed
     */
    public static function cleanFormData($data)
    {
        if (!empty($data['formName'])) {
            $formName = $data['formName'];
            $formModel = Application_Model_Mappers_FormMapper::getInstance()->findByName($formName);
            if ($formModel instanceof Application_Model_Models_Form) {
                unset($data[md5($formName . $formModel->getId())]);
            }
        }

        unset($data['controller']);
        unset($data['action']);
        unset($data['module']);
        unset($data['formName']);
        unset($data['captcha']);
        unset($data['captchaId']);
        unset($data['recaptcha']);
        unset($data['recaptcha_challenge_field']);
        unset($data['recaptcha_response_field']);
        unset($data['formPageId']);
        unset($data['submit']);
        unset($data['uploadLimitSize']);
        unset($data['g-recaptcha-response']);
        unset($data['run']);
        unset($data['handle']);
        unset($data['pid']);
        if (isset($data['conversionPageUrl'])) {
            unset($data['conversionPageUrl']);
        }

        return $data;
    }

}