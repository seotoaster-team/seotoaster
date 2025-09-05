<?php

class Tools_System_WebsiteLog
{

    /**
     *
     * Record website action log entity
     *
     * @param $actionType
     * @param $name
     * @param array $rawData
     * @return Application_Model_Models_WebsiteActionLog|null
     */

    public static function recordToWebsiteLog($actionType, $name, $rawData = array())
    {

        $ipAddress = Tools_System_Tools::getIpAddress();
        $fingerprint = Tools_System_Tools::getBrowserFingerprint();
        $createdAt = Tools_System_Tools::convertDateFromTimezone('now');
        $rawData = Tools_System_FormBlacklist::cleanFormData($rawData);
        $email = null;
        if (isset($rawData['email']) && Tools_System_Tools::isEmailValid($rawData['email'])) {
            $email = $rawData['email'];
        }

        $rawDataJson = json_encode($rawData);

        $websiteActionLogMapper = Application_Model_Mappers_WebsiteActionLogMapper::getInstance();
        $websiteActionLogModel = new Application_Model_Models_WebsiteActionLog();
        $websiteActionLogModel->setActionType($actionType);
        $websiteActionLogModel->setName($name);
        $websiteActionLogModel->setEmail($email);
        $websiteActionLogModel->setIpAddress($ipAddress);
        $websiteActionLogModel->setBrowserFingerprint($fingerprint);
        $websiteActionLogModel->setCreatedAt($createdAt);
        $websiteActionLogModel->setRawData($rawDataJson);
        try {
            return $websiteActionLogMapper->save($websiteActionLogModel);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return null;
        }

    }

    /**
     * Check if block is active
     *
     * @return bool
     */
    public static function isBlocked()
    {
        $ipAddress = Tools_System_Tools::getIpAddress();
        $now = Tools_System_Tools::convertDateFromTimezone('now');
        $actionTypes = array('block', 'cooldown');

        $websiteVisitorsBacklogMapper = Application_Model_Mappers_WebsiteVisitorsBacklogMapper::getInstance();
        $result = $websiteVisitorsBacklogMapper->isActiveBlock($ipAddress, $now, $actionTypes);
        if (!empty($result)) {
            return true;
        }
        return false;
    }


}