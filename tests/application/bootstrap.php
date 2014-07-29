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
error_reporting(E_ALL | E_STRICT);

/*************************************************************************
 * Reading current core config
 *
 *************************************************************************/
$coreConfigPath = realpath(dirname(__FILE__) . '/../../system/coreinfo.php');

if (file_exists($coreConfigPath)) {
    require_once realpath($coreConfigPath);
}

defined('CORE') || define('CORE', realpath(dirname(__FILE__) . '/../../seotoaster_core/'));
defined('SITE_NAME') || define('SITE_NAME', '');

/* End reading current core config */

// Define path to application directory
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', CORE . 'application');

// Define application environment
defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'testing'));

// header to prevent security issues in iframes (to avoid clickjacking attacks)
header('X-Frame-Options: SAMEORIGIN');

// Ensure library/ is on include_path
set_include_path(
    implode(
        PATH_SEPARATOR,
        array(
            realpath(APPLICATION_PATH . '/../library'),
            get_include_path(),
        )
    )
);

/** Zend_Application */
require_once 'Zend/Application.php';
require_once 'ControllerTestCase.php';
require_once 'Zend/Config/Ini.php';
require_once 'Zend/Cache.php';

$configIni = new Zend_Config_Ini(APPLICATION_PATH . '/configs/' . SITE_NAME . '.ini');
$cacheFrontendOptions = $configIni->cache->cache->frontend->toArray();
$cacheBackendOptions = $configIni->cache->cache->backend->toArray();
$cache = Zend_Cache::factory('Core', 'File', $cacheFrontendOptions, $cacheBackendOptions);
$cache->clean();
