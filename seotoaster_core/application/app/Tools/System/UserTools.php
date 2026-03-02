<?php

/**
 * User tools
 */
class Tools_System_UserTools
{
    const PROFILE_IMAGE_FOLDER = 'profile_pictures';

    const PROFILE_IMAGE_WEB_PATH = 'system/images/'.self::PROFILE_IMAGE_FOLDER.'/';

    const PROFILE_IMAGE_PATH = 'system'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR;


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

}
