<?php

/**
 * Class Api_Toaster_Containers
 * Seotoaster profile image processing API
 */
class Api_Toaster_Profileimage extends Api_Service_Abstract
{

    const PROFILE_IMAGE_SECURE_TOKEN = 'profileImageSecureToken';

    protected $_accessList = array(
        Tools_Security_Acl::ROLE_SUPERADMIN => array('allow' => array('get', 'post', 'put', 'delete')),
        Tools_Security_Acl::ROLE_ADMIN => array('allow' => array('get', 'post', 'put', 'delete')),
        Tools_Security_Acl::ROLE_MEMBER => array('allow' => array('get', 'post', 'put', 'delete')),
    );

    public function init()
    {
        $this->_websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
    }

    public function getAction()
    {
    }

    public function postAction()
    {

        $tokenToValidate = $this->_request->getParam(Tools_System_Tools::CSRF_SECURE_TOKEN, false);
        $valid = Tools_System_Tools::validateToken($tokenToValidate, self::PROFILE_IMAGE_SECURE_TOKEN);
        if (!$valid) {
            exit;
        }

        $translator = Zend_Registry::get('Zend_Translate');

        $imageData = $this->_request->getParams();

        if (empty($imageData['userId'])) {
            return array('error' => 1, 'message' => $translator->translate('User id is missing'));
        }

        $userId = $imageData['userId'];

        $userMapper = Application_Model_Mappers_UserMapper::getInstance();
        $userModel = $userMapper->find($userId);
        if (!$userModel instanceof Application_Model_Models_User) {
            return array('error' => 1, 'message' => $translator->translate('User not found'));
        }

        if (empty($imageData['imageName'])) {
            return array('error' => 1, 'message' => $translator->translate('Image name is missing'));
        }

        $session = Zend_Controller_Action_HelperBroker::getStaticHelper('session');
        $userRole = $session->getCurrentUser()->getRoleId();
        $loggedUserId = $session->getCurrentUser()->getId();
        if ($userRole !== Tools_Security_Acl::ROLE_SUPERADMIN && $userRole !== Tools_Security_Acl::ROLE_ADMIN) {
            if ((int)$loggedUserId !== (int)$userId) {
                return array('error' => 1, 'message' => $translator->translate('You are not allowed to change profile image'));
            }
        }

        $imageName = $imageData['imageName'];
        $thumbSize = Tools_System_UserTools::DEFAULT_PROFILE_IMAGE_SIZE;
        $pathToDirectory = $this->_websiteHelper->getPath() . $this->_websiteHelper->getTmp();
        $temporaryFilePath = $pathToDirectory . $imageName;
        $pathToProfileImageDirectory = Tools_System_UserTools::getProfileFolderPath();

        Tools_Image_Tools::resizeByParameters(
            $temporaryFilePath,
            $thumbSize,
            'auto',
            true,
            $pathToProfileImageDirectory
        );

        if (file_exists($temporaryFilePath)) {
            Tools_Filesystem_Tools::deleteFile($temporaryFilePath);
        }

        Tools_System_UserTools::deleteProfilePicture($userId);
        $userModel->setProfileImage($imageName);
        $userMapper->save($userModel);

        $src = Tools_System_UserTools::getFullProfileImageLink($userId);

        return array('error' => 0, 'src' => $src, 'message' => $translator->translate('Profile image has been uploaded'));

    }

    public function putAction()
    {
    }

    public function deleteAction()
    {

        $userId = $this->_request->getParam('userId');
        $translator = Zend_Registry::get('Zend_Translate');

        if (empty($userId)) {
            return array('error' => 1, 'message' => $translator->translate('User id is missing'));
        }

        $session = Zend_Controller_Action_HelperBroker::getStaticHelper('session');
        $userRole = $session->getCurrentUser()->getRoleId();
        $loggedUserId = $session->getCurrentUser()->getId();
        if ($userRole !== Tools_Security_Acl::ROLE_SUPERADMIN && $userRole !== Tools_Security_Acl::ROLE_ADMIN) {
            if ((int)$loggedUserId !== (int)$userId) {
                return array('error' => 1, 'message' => $translator->translate('You are not allowed to change profile image'));
            }
        }

        Tools_System_UserTools::deleteProfilePicture($userId);

        return array('error' => 0, 'placeHolderImage' => Tools_System_UserTools::getProfilePlaceholderImage(), 'message' => $translator->translate('Profile image has been deleted'));

    }
}