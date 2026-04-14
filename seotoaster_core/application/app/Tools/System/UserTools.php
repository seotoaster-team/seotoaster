<?php

/**
 * User tools
 */
class Tools_System_UserTools
{
    const TIME_FORMAT_12H = '12h';

    const TIME_FORMAT_24H = '24h';

    const PROFILE_IMAGE_FOLDER = 'profile_pictures';

    const PROFILE_IMAGE_WEB_PATH = 'system/images/'.self::PROFILE_IMAGE_FOLDER.'/';

    const PROFILE_IMAGE_PATH = 'system'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR;

    const PLACEHOLDER_IMAGE = 'system'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'profile-placeholder.png';

    const DEFAULT_PROFILE_IMAGE_SIZE = 250;

    public static $_twelveHoursFormat = array(
        '00' => '12:00 AM',
        '1' => '1:00 AM',
        '2' => '2:00 AM',
        '3' => '3:00 AM',
        '4' => '4:00 AM',
        '5' => '5:00 AM',
        '6' => '6:00 AM',
        '7' => '7:00 AM',
        '8' => '8:00 AM',
        '9' => '9:00 AM',
        '10' => '10:00 AM',
        '11' => '11:00 AM',
        '12' => '12:00 PM',
        '13' => '1:00 PM',
        '14' => '2:00 PM',
        '15' => '3:00 PM',
        '16' => '4:00 PM',
        '17' => '5:00 PM',
        '18' => '6:00 PM',
        '19' => '7:00 PM',
        '20' => '8:00 PM',
        '21' => '9:00 PM',
        '22' => '10:00 PM',
        '23' => '11:00 PM',
    );

    public static $_twentyFourHoursFormat = array(
        '00' => '00:00',
        '1'  => '01:00',
        '2'  => '02:00',
        '3'  => '03:00',
        '4'  => '04:00',
        '5'  => '05:00',
        '6'  => '06:00',
        '7'  => '07:00',
        '8'  => '08:00',
        '9'  => '09:00',
        '10' => '10:00',
        '11' => '11:00',
        '12' => '12:00',
        '13' => '13:00',
        '14' => '14:00',
        '15' => '15:00',
        '16' => '16:00',
        '17' => '17:00',
        '18' => '18:00',
        '19' => '19:00',
        '20' => '20:00',
        '21' => '21:00',
        '22' => '22:00',
        '23' => '23:00',
    );

    /**
     * Get full profile image link
     *
     * @param int $userId system user id
     * @return string
     */
    public static function getFullProfileImageLink($userId)
    {
        $userModel = Application_Model_Mappers_UserMapper::getInstance()->find($userId);
        if (!$userModel instanceof Application_Model_Models_User) {
            return '';
        }

        $profileImage = $userModel->getProfileImage();
        if (empty($profileImage)) {
           return '';
        }

        $profileImagePath = self::getProfileFolderPath().$profileImage;
        if (!file_exists($profileImagePath)) {
            return '';
        }

        $websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
        $profileImageUrl = $websiteHelper->getUrl().self::PROFILE_IMAGE_WEB_PATH.$profileImage;

        return $profileImageUrl;
    }


    /**
     * Get path to the profile images folder
     *
     * @return string
     */
    public static function getProfileFolderPath()
    {
        $websiteHelper  = Zend_Controller_Action_HelperBroker::getStaticHelper('website');

        return $websiteHelper->getPath().self::PROFILE_IMAGE_PATH.self::PROFILE_IMAGE_FOLDER.DIRECTORY_SEPARATOR;
    }

    /**
     * Delete profile picture
     *
     * @param int $userId user id
     * @return array
     * @throws Exceptions_SeotoasterException
     * @throws Zend_Exception
     */
    public static function deleteProfilePicture($userId)
    {
        $translator = Zend_Registry::get('Zend_Translate');

        $userMapper = Application_Model_Mappers_UserMapper::getInstance();
        $userModel = $userMapper->find($userId);
        if (!$userModel instanceof Application_Model_Models_User) {
            return array('error' => 1, 'message' => $translator->translate('User not found'));
        }

        $profileImage = $userModel->getProfileImage();

        if (empty($profileImage)) {
            return array('error' => 1, 'message' => $translator->translate('Profile image not found'));
        }

        $fullImagePath = self::getProfileFolderPath().$profileImage;
        if (!file_exists($fullImagePath)) {
            return array('error' => 1, 'message' => $translator->translate('Profile picture not found'));
        }

        Tools_Filesystem_Tools::deleteFile($fullImagePath);
        $userModel->setProfileImage(null);
        $userMapper->save($userModel);

        return array('error' => 0);
    }

    /**
     * Get profile placeholder image
     *
     * @return string
     */
    public static function getProfilePlaceholderImage()
    {
        $websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
        $profilePlaceholderImageUrl = $websiteHelper->getUrl() . self::PLACEHOLDER_IMAGE;

        return $profilePlaceholderImageUrl;
    }

    /**
     * Get time format items list
     *
     * @param int $userId
     * @return array
     */
    public static function getTimeFormatList($userId = 0)
    {
        if (empty($userId)) {
            $loggedUserInfo = Zend_Controller_Action_HelperBroker::getStaticHelper('session')->getCurrentUser();
            $userId = $loggedUserInfo->getId();
        }

        if (!empty($userId)) {
            $userMapper = Application_Model_Mappers_UserMapper::getInstance();
            $userModel = $userMapper->find($userId);
            if (!$userModel instanceof Application_Model_Models_User) {
                return self::$_twelveHoursFormat;
            }

            $timeFormat = $userModel->getTimeFormat();
            if ($timeFormat === self::TIME_FORMAT_12H) {
                return self::$_twelveHoursFormat;
            } elseif ($timeFormat === self::TIME_FORMAT_24H) {
                return self::$_twentyFourHoursFormat;
            } else {
                return self::$_twelveHoursFormat;
            }
        } else {
            return self::$_twelveHoursFormat;
        }
    }

}
