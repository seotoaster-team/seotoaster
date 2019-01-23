<?php

class Tools_System_FormBlacklist
{
    /**
     * Check whether params are blacklisted
     *
     * @param string $email email address
     * @param string $ipAddress email address
     * @return bool
     */
    public static function isBlacklisted($email, $ipAddress = '')
    {
        $params = array('1' => array('type' => 'domain', 'value' => 5), '2' => array('type' => 'email', 'value' => 'sdfsdf'));
        $formBlacklistRulesMapper = Application_Model_Mappers_FormBlacklistRulesMapper::getInstance();
        $formBlacklistRulesMapper->deleteAllData();
        if (!empty($params)) {
            $importData = array();
            foreach ($params as $paramData) {
                $importData[] = $paramData['type'];
                $importData[] = $paramData['value'];

            }
            $headers = array('type', 'value');
            $columnNames = implode($headers, ', ');
            $whereStm = substr(str_repeat('?,', count($headers)), 0, -1);
            $quantity = count($importData) / count($headers);
            $values = implode(
                ',',
                array_fill(0, $quantity, '(' . $whereStm . ')')
            );
            $importStmt = $formBlacklistRulesMapper->getDbTable()->getAdapter()
                ->prepare(
                    'INSERT INTO ' . $formBlacklistRulesMapper->getDbTable()->info(Zend_Db_Table::NAME) . ' (' . $columnNames . ') VALUES ' . $values . ''
                );
            try {
                $importStmt->execute($importData);
                $data['done'] = true;
            } catch (Exception $e) {
                $data['done'] = false;
            }
        }

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



}