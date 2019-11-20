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


}