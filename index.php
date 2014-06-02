<?php
/*************************************************************************
 * Necessary alter for pcre.backtrack_limit
 *
 ************************************************************************/
ini_set('pcre.backtrack_limit', '10000000000000');

ini_set('session.cookie_httponly', 1);

/*************************************************************************
 * Installation check
 *
 ************************************************************************/

if(is_dir(realpath(dirname(__FILE__).'/install/'))){
	header('Location: install/');
	exit();
}

/* End installation check */


/*************************************************************************
 * Reading current core config
 *
 *************************************************************************/
$coreConfigPath = realpath(dirname(__FILE__).'/system/coreinfo.php');

if(file_exists($coreConfigPath)) {
	require_once realpath($coreConfigPath);
}

defined('CORE')      || define('CORE', realpath(dirname(__FILE__) . '/seotoaster_core/'));
defined('SITE_NAME') || define('SITE_NAME', '');

/* End reading current core config */


// Define path to application directory
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', CORE . 'application');

// Define application environment
defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(APPLICATION_PATH . '/../library'),
    get_include_path(),
)));

// header to prevent security issues in iframes (to avoid clickjacking attacks)
header('X-Frame-Options: SAMEORIGIN');

/** Zend_Application */
require_once 'Zend/Application.php';

// Create application, bootstrap, and run
$application = new Zend_Application(
    APPLICATION_ENV,
    APPLICATION_PATH . '/configs/' . SITE_NAME . '.ini'
);
$application->bootstrap()
            ->run();