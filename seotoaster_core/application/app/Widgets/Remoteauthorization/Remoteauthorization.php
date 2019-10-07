<?php

class Widgets_Remoteauthorization_Remoteauthorization extends Widgets_Abstract
{

    /**
     * Cache flag, shows whether this widget cached
     *
     * @var bool
     */
    protected $_cacheable = false;

    /**
     * Website url
     *
     * @var string
     */
    protected $_websiteUrl = '';

    /**
     * Toaster website helper
     *
     * @var Helpers_Action_Website
     */
    protected $_websiteHelper = null;

    /**
     * Toaster config helper
     *
     * @var Helpers_Action_Config
     */
    protected $_configHelper = null;

    /**
     * Toaster session helper
     *
     * @var Helpers_Action_Session
     */
    protected $_sessionHelper = null;

    /**
     * Path to plugin's languages files
     *
     * @var string
     */
    protected $_languagesPath = 'system/languages/';

    /**
     * @var null
     */
    protected $_userAuthorizationModel = null;

    /**
     * Set of authorization params such as redirectLink, backLink, userMojoName
     *
     * @var array
     */
    protected $_userAuthorizationParams = array();

    /**
     * Methods which require authorization
     *
     * @var array
     */
    public static $_authorizationRequiredMethods = array('Redirectlink', 'Backlink', 'Usermojoname');

    /**
     *
     * init
     *
     * @throws Zend_Exception
     */
    protected function _init()
    {
        parent::_init();
        $this->_view             = new Zend_View(array('scriptPath' => dirname(__FILE__).'/views'));
        // init helpers
        $website                 = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
        $this->_view->websiteUrl = $website->getUrl();
        $this->_configHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('config');
        $this->_sessionHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('session');
    }

    /**
     * render widgets
     *
     * @return mixed
     */
    protected function _load()
    {
        $optionName = ucfirst(strtolower($this->_options[0]));
        $methodName = '_render' . $optionName;
        $currentUser = $this->_sessionHelper->getCurrentUser();
        $roleId = $currentUser->getRoleId();
        $userId = $currentUser->getId();
        if (in_array($optionName, self::$_authorizationRequiredMethods)) {
            return '';
        }

        if (in_array($optionName, self::$_authorizationRequiredMethods)) {
            if (!Zend_Registry::isRegistered('userAuthorizationParams') || !Zend_Registry::isRegistered('userAuthorizationModel')) {
                $userMapper = Application_Model_Mappers_UserMapper::getInstance();
                $userModel = $userMapper->find($userId);
                if (!$userModel instanceof Application_Model_Models_User) {
                    return '';
                } else {
                    $this->_userAuthorizationModel = $userModel;
                    $this->_userAuthorizationParams = json_decode($userModel->getRemoteAuthorizationInfo(),
                        true);
                    Zend_Registry::set('userAuthorizationModel', $this->_userAuthorizationModel);
                    Zend_Registry::set('userAuthorizationParams', $this->_userAuthorizationParams);
                }
            } else {
                $this->_userAuthorizationModel = Zend_Registry::get('userAuthorizationModel');
                $this->_userAuthorizationParams = Zend_Registry::get('userAuthorizationParams');
            }


        }

        if (method_exists($this, $methodName)) {
            return $this->$methodName();
        }

        return $this->_translator->translate('method not exists');
    }

    /**
     * user last login redirect page url
     *
     * @return mixed
     */
    private function _renderRedirectLink()
    {
        return $this->_userAuthorizationParams['redirectLink'];
    }

    /**
     * mojo profile link
     *
     * @return mixed
     */
    private function _renderBackLink()
    {
        return $this->_userAuthorizationParams['backLink'];
    }

    /**
     * User mojo name and service name
     *
     * @return mixed
     */
    private function _renderUserMojoName()
    {
        return $this->_userAuthorizationParams['userMojoName'];
    }


}
