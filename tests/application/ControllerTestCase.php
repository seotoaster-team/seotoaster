<?php

require_once 'Zend/Application.php';
require_once 'Zend/Test/PHPUnit/ControllerTestCase.php';

abstract class ControllerTestCase extends Zend_Test_PHPUnit_ControllerTestCase
{
    /**
     * @var Zend_Application
     */
    protected $_application;

    public function setUp()
    {
        if (Zend_Registry::isRegistered('session')) {
            $session = Zend_Registry::get('session');
            $session->unLock();
            Zend_Session_Namespace::resetSingleInstance('toaster_' . SITE_NAME);
        }
        $this->bootstrap = array($this, 'appBootstrap');
        parent::setUp();
        $this->_configIni = new Zend_Config_Ini(APPLICATION_PATH . '/configs/' . SITE_NAME . '.ini');
        $websiteConfig = $this->_configIni->website->website->toArray();
        $_SERVER['DOCUMENT_ROOT'] = rtrim($websiteConfig['path'], "/");
        $_SERVER['SCRIPT_FILENAME'] = $websiteConfig['path'] . 'index.php';
        $_SERVER['HTTP_HOST'] = '';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['QUERY_STRING'] = '';
        $_SERVER['SERVER_NAME'] = '';
        $_SERVER['HTTP_HOST'] = rtrim($websiteConfig['url'], '/');
        $_SERVER['HTTP_ORIGIN'] = 'http://' . $_SERVER['HTTP_HOST'];
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    }

    public function appBootstrap()
    {

        $this->_application = new Zend_Application(
            APPLICATION_ENV,
            APPLICATION_PATH . '/configs/' . SITE_NAME . '.ini'
        );

        $this->_application->bootstrap();
    }
}
