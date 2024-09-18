<?php

/**
 * Mfa tools (2FA)
 */
class Tools_System_MfaTools
{

    const VERIFICATION_CODE_LENGTH = 6;

    /**
     * Send mfa notification
     *
     * @param int $userId user id
     * @return array
     */
    public static function sendMfaNotification($userId)
    {

        if (self::isUserEligibleForMfa($userId) === false) {
            return array('error' => 0, 'message' => '', 'data' => array());
        }

        $result = self::sendMfaEmail($userId);
        if ($result === true) {
            return array('error' => 0, 'message' => '', 'data' => array());
        }

        return array('error' => 1, 'message' => 'Please contact website administrator', 'data' => array());
    }

    /**
     * Generate email mfa code
     *
     * @return int
     */
    public static function generateEmailMfaCode()
    {
        return mt_rand(100000, 999999);
    }

    /**
     * Send Mfa email
     *
     * @param int $userId user id
     * @return bool
     */
    public static function sendMfaEmail($userId)
    {
        $userMapper = Application_Model_Mappers_UserMapper::getInstance();
        $userModel = $userMapper->find($userId);
        if (!$userModel instanceof Application_Model_Models_User) {
            return false;
        }

        $mfaCode = self::generateEmailMfaCode();

        $userModel->setMfaCode($mfaCode);
        $userModel->setPassword(null);
        $userModel->setMfaCodeExpirationTime(Tools_System_Tools::convertDateFromTimezone('+10 minute', 'UTC', 'UTC'));
        $userMapper->save($userModel);

        $userModel->removeAllObservers();
        $userModel->registerObserver(new Tools_Mail_Watchdog(array(
            'trigger' => Tools_Mail_SystemMailWatchdog::TRIGGER_MFANOTIFICATION,
            'ipAddress' => Tools_System_Tools::getIpAddress()
        )));
        $userModel->notifyObservers();

        return true;
    }

    /**
     *
     * Verify whether user eligible to receive mfa notification
     *
     * @param int $userId user id
     * @return bool
     */
    public static function isUserEligibleForMfa($userId)
    {
        $userModel = Application_Model_Mappers_UserMapper::getInstance()->find($userId);
        if (!$userModel instanceof Application_Model_Models_User) {
            return false;
        }

        $configHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('config');
        $activeMfa = $configHelper->getConfig('activateMfa');
        if (empty($activeMfa)) {
            return false;
        }

        $enabledMfa = $userModel->getEnabledMfa();
        if (empty($enabledMfa)) {
            return false;
        }

        return true;
    }

    /**
     * Clean expired verification code (user attempt to verify)
     */
    public static function cleanVerificationUserId()
    {
        $session = Zend_Controller_Action_HelperBroker::getStaticHelper('session');
        if (isset($session->verificationCodeUserIdExpiresAt) && isset($session->verificationCodeUserId)) {
            if (strtotime(Tools_System_Tools::convertDateFromTimezone('now', 'UTC', 'UTC')) > strtotime($session->verificationCodeUserIdExpiresAt)) {
                unset($session->verificationCodeUserIdExpiresAt);
                unset($session->verificationCodeUserId);
            }
        }
    }

    /**
     * Verify if verification code is valid
     *
     * @param int $userId
     * @param int $verificationCode
     * @return bool
     */
    public static function isVerificationCodeValid($userId, $verificationCode)
    {
        $verificationCodeLength = strlen($verificationCode);
        if ($verificationCodeLength !== self::VERIFICATION_CODE_LENGTH) {
            return false;
        }

        $userMapper = Application_Model_Mappers_UserMapper::getInstance();
        $userModel = $userMapper->find($userId);
        if (!$userModel instanceof Application_Model_Models_User) {
            return false;
        }

        $originalVerificationCode = $userModel->getMfaCode();
        if ((int)$verificationCode !== (int)$originalVerificationCode) {
            return false;
        }

        $mfaCodeExpirationTime = $userModel->getMfaCodeExpirationTime();

        if (strtotime(Tools_System_Tools::convertDateFromTimezone('now', 'UTC', 'UTC')) > strtotime($mfaCodeExpirationTime)) {
            return false;
        }

        return true;

    }
}
