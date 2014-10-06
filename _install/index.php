<?php
/**
 * SEOTOASTER 2.0 installer
 */
if (version_compare(PHP_VERSION, '5.3.2', '<')){
	die('Sorry you need PHP 5.3.3 version or greater. Your version is: ' . PHP_VERSION . '.');
}
define('INSTALL_PATH', realpath(__DIR__.'/../'));
define('INSTALLER_PATH', INSTALL_PATH.'/install');

// Define path to ZendFramework library
define('ZFLIBPATH', realpath(__DIR__.'/../seotoaster_core/library'));

// Define path to installer directory
define('APPLICATION_PATH', INSTALLER_PATH.'/installer');

// Define application environment
define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    ZFLIBPATH,
    get_include_path(),
)));

/** Zend_Application */
require_once 'Zend/Application.php';

// Create application, bootstrap, and run
$application = new Zend_Application(
    APPLICATION_ENV,
    APPLICATION_PATH . '/configs/installer.ini'
);
$application->bootstrap()->run();