<?php

/**
 * User tools
 */
class Tools_System_UserTools
{
    const PROFILE_IMAGE_FOLDER = 'profile_pictures';

    const PROFILE_IMAGE_WEB_PATH = 'system/images/'.self::PROFILE_IMAGE_FOLDER.'/';

    const PROFILE_IMAGE_PATH = 'system'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR;

    const PLACEHOLDER_IMAGE = 'system'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'profile-placeholder.png';

    const DEFAULT_PROFILE_IMAGE_SIZE = 250;


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

}
