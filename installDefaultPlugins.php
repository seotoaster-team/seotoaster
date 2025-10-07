<?php

if (PHP_SAPI !== 'cli') {
    die('direct access is not allowed');
}

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
//error_reporting(E_ALL | E_STRICT);

/*************************************************************************
 * Reading current core config
 *
 *************************************************************************/
$coreConfigPath = realpath(__DIR__ . '/system/coreinfo.php');
if (file_exists($coreConfigPath)) {
    require_once realpath($coreConfigPath);
}
defined('CORE') || define('CORE', realpath(__DIR__ . '/seotoaster_core/'));
defined('SITE_NAME') || define('SITE_NAME', '');

/* End reading current core config */

// Define path to application directory
defined('APPLICATION_PATH')
|| define('APPLICATION_PATH', CORE . 'application');

// Define application environment
defined('APPLICATION_ENV')
|| define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'testing'));

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

$application = new Zend_Application(
    APPLICATION_ENV,
    APPLICATION_PATH . '/configs/' . SITE_NAME . '.ini'
);

if (Zend_Registry::isRegistered('session')) {
    $session = Zend_Registry::get('session');
    $session->unLock();
    Zend_Session_Namespace::resetSingleInstance('toaster_' . SITE_NAME);
}

set_include_path(realpath(APPLICATION_PATH . '/app') . PATH_SEPARATOR . get_include_path());

$configIni = new Zend_Config_Ini(APPLICATION_PATH . '/configs/' . SITE_NAME . '.ini');
Zend_Registry::set('website', $configIni->website->website->toArray());
Zend_Registry::set('database', $configIni->database->database->toArray());
Zend_Registry::set('theme', $configIni->theme->theme->toArray());
Zend_Registry::set('news', $configIni->news->news->toArray());
Zend_Registry::set('misc', $configIni->misc->misc->toArray());

$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->registerNamespace('Widgets_');
$autoloader->registerNamespace('MagicSpaces_');
$autoloader->registerNamespace('Interfaces_');
$autoloader->registerNamespace('Helpers_');
$autoloader->registerNamespace('Exceptions_');
$autoloader->registerNamespace('Tools_');
$autoloader->registerNamespace('Plugins_');
$autoloader->registerNamespace('Api_');
$autoloader->setFallbackAutoloader(true);

$session = new Zend_Session_Namespace('toaster_' . SITE_NAME, true);
Zend_Registry::set('session', $session);


$websiteConfig = $configIni->website->website->toArray();

Zend_Controller_Action_HelperBroker::addHelper(new Helpers_Action_Page());
//Zend_Controller_Action_HelperBroker::addHelper(new Helpers_Action_Cache());
Zend_Controller_Action_HelperBroker::addHelper(new Helpers_Action_Session());
Zend_Controller_Action_HelperBroker::addHelper(new Helpers_Action_Admin());
Zend_Controller_Action_HelperBroker::addHelper(new Helpers_Action_Config());
Zend_Controller_Action_HelperBroker::addHelper(new Helpers_Action_Website());
Zend_Controller_Action_HelperBroker::addHelper(new Helpers_Action_Response());
Zend_Controller_Action_HelperBroker::addHelper(new Helpers_Action_Language());
Zend_Controller_Action_HelperBroker::addHelper(new Helpers_Action_Mobile());
Zend_Controller_Action_HelperBroker::addHelper(new Zend_Controller_Action_Helper_Json());


$dbConfig = $configIni->database->database->toArray();
$adapter = strtolower($dbConfig['adapter']);
if (!in_array($adapter, array('pdo_mysql', 'mysqli'))) {
    if (extension_loaded('pdo_mysql')) {
        $adapter = 'pdo_mysql';
    } elseif (extension_loaded('mysqli')) {
        $adapter = 'mysqli';
    } else {

    }
}
if ($adapter === 'pdo_mysql') {
    $dbConfig['params']['driver_options'] = array(
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES UTF8;',
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
    );
}
$database = Zend_Db::factory($adapter, $dbConfig['params']);
if ($adapter === 'mysqli') {
    $database->query('SET NAMES UTF8');
    $database->query('SET CHARACTER SET utf8');
}
Zend_Db_Table_Abstract::setDefaultAdapter($database);
Zend_Registry::set('dbAdapter', $database);


$config = Application_Model_Mappers_ConfigMapper::getInstance()->getConfig();
$name = Zend_Locale::getLocaleToTerritory($config['language']);
if ($name !== null) {
    $locale = new Zend_Locale($name);
} else {
    $locale = new Zend_Locale();
}

Zend_Registry::set('Zend_Locale', $locale);

if (!empty($config['useSqlMode'])) {
    $database->query("SET sql_mode=''");
    Zend_Db_Table_Abstract::setDefaultAdapter($database);
    Zend_Registry::set('dbAdapter', $database);
}

$session = Zend_Registry::get('session');

$locale = (isset($session->locale)) ? $session->locale : Zend_Registry::get('Zend_Locale');

$session->locale = $locale;

$translator = new Zend_Translate(array(
    'adapter' => 'array',
    'content' => $websiteConfig['path'] . 'system/languages/',
    'scan' => Zend_Translate::LOCALE_FILENAME,
    'locale' => $locale->getLanguage(),
    'ignore' => array('.'),
    'route' => array('fr' => 'en', 'it' => 'en', 'de' => 'en')
));

//		Zend_Form::setDefaultTranslator($translator);
Zend_Registry::set('Zend_Locale', $locale);
Zend_Registry::set('Zend_Translate', $translator);
Zend_Registry::set('session', $session);


$view = new Zend_View();
$website = Zend_Registry::get('website');
$misc = Zend_Registry::get('misc');
$url = preg_replace('~^https?://~', '', $website['url']);
$request = new Zend_Controller_Request_Http();
$protocol = $request->getScheme();

$view->addHelperPath('ZendX/JQuery/View/Helper/', 'ZendX_JQuery_View_Helper');
if ($misc['jquery'] == 'local') {
    $view->jQuery()->setLocalPath($protocol . '://' . $url . 'system/js/external/jquery/jquery.js');
} else {
    $view->jQuery()
        ->setCdnSsl($request->isSecure())
        ->setVersion($misc['jqversion']);
}
if ($misc['jqueryui'] == 'local') {
    $view->jQuery()->setUiLocalPath($protocol . '://' . $url . 'system/js/external/jquery/jquery-ui.js');
} else {
    $view->jQuery()->setUiVersion($misc['jquversion']);
}

$viewRenderer = new Zend_Controller_Action_Helper_ViewRenderer($view);
Zend_Controller_Action_HelperBroker::addHelper($viewRenderer);


$routesXmlPath = is_file(
    APPLICATION_PATH . '/configs/' . SITE_NAME . '.routes.xml'
) ? APPLICATION_PATH . '/configs/' . SITE_NAME . '.routes.xml' : APPLICATION_PATH . '/configs/routes.xml';
$routes = new Zend_Config_Xml($routesXmlPath);
$router = Zend_Controller_Front::getInstance()->getRouter();
$router->addConfig($routes, 'routes');

/*$application->bootstrap()
    ->run();*/

$acl = new Zend_Acl();

// roles: member, user, admin, super admin
$acl->addRole(new Zend_Acl_Role(Tools_Security_Acl::ROLE_GUEST));
$acl->addRole(new Zend_Acl_Role(Tools_Security_Acl::ROLE_MEMBER), Tools_Security_Acl::ROLE_GUEST);
$acl->addRole(new Zend_Acl_Role(Tools_Security_Acl::ROLE_USER), Tools_Security_Acl::ROLE_MEMBER);
$acl->addRole(new Zend_Acl_Role(Tools_Security_Acl::ROLE_ADMIN));
$acl->addRole(new Zend_Acl_Role(Tools_Security_Acl::ROLE_SUPERADMIN));

//resources
$acl->addResource(new Zend_Acl_Resource(Tools_Security_Acl::RESOURCE_CONTENT));
$acl->addResource(new Zend_Acl_Resource(Tools_Security_Acl::RESOURCE_WIDGETS));
$acl->addResource(new Zend_Acl_Resource(Tools_Security_Acl::RESOURCE_PAGE_PROTECTED));
$acl->addResource(new Zend_Acl_Resource(Tools_Security_Acl::RESOURCE_PAGE_PUBLIC));
$acl->addResource(new Zend_Acl_Resource(Tools_Security_Acl::RESOURCE_CACHE_PAGE));
$acl->addResource(new Zend_Acl_Resource(Tools_Security_Acl::RESOURCE_CODE));
$acl->addResource(new Zend_Acl_Resource(Tools_Security_Acl::RESOURCE_THEMES));
//resources of admin area
$acl->addResource(new Zend_Acl_Resource(Tools_Security_Acl::RESOURCE_ADMINPANEL));
$acl->addResource(new Zend_Acl_Resource(Tools_Security_Acl::RESOURCE_PAGES));
$acl->addResource(new Zend_Acl_Resource(Tools_Security_Acl::RESOURCE_MEDIA));
$acl->addResource(new Zend_Acl_Resource(Tools_Security_Acl::RESOURCE_SEO));
$acl->addResource(new Zend_Acl_Resource(Tools_Security_Acl::RESOURCE_LAYOUT));
$acl->addResource(new Zend_Acl_Resource(Tools_Security_Acl::RESOURCE_CONFIG));
$acl->addResource(new Zend_Acl_Resource(Tools_Security_Acl::RESOURCE_USERS));
$acl->addResource(new Zend_Acl_Resource(Tools_Security_Acl::RESOURCE_PLUGINS));
$acl->addResource(new Zend_Acl_Resource(Tools_Security_Acl::RESOURCE_PLUGINS_MENU));

//permissions
$acl->allow(Tools_Security_Acl::ROLE_GUEST, Tools_Security_Acl::RESOURCE_PAGE_PUBLIC);
$acl->allow(Tools_Security_Acl::ROLE_GUEST, Tools_Security_Acl::RESOURCE_CACHE_PAGE);

$acl->deny(Tools_Security_Acl::ROLE_MEMBER, Tools_Security_Acl::RESOURCE_CACHE_PAGE);
$acl->allow(Tools_Security_Acl::ROLE_MEMBER, Tools_Security_Acl::RESOURCE_PAGE_PROTECTED);
$acl->allow(Tools_Security_Acl::ROLE_MEMBER, Tools_Security_Acl::RESOURCE_ADMINPANEL);
$acl->allow(Tools_Security_Acl::ROLE_MEMBER, Tools_Security_Acl::RESOURCE_PLUGINS_MENU);

//user = copywriter
$acl->allow(Tools_Security_Acl::ROLE_USER, Tools_Security_Acl::RESOURCE_PLUGINS);
$acl->allow(Tools_Security_Acl::ROLE_USER, Tools_Security_Acl::RESOURCE_ADMINPANEL);
$acl->allow(Tools_Security_Acl::ROLE_USER, Tools_Security_Acl::RESOURCE_CONTENT);
$acl->allow(Tools_Security_Acl::ROLE_USER, Tools_Security_Acl::RESOURCE_MEDIA);
$acl->allow(Tools_Security_Acl::ROLE_USER, Tools_Security_Acl::RESOURCE_PAGES);
$acl->allow(Tools_Security_Acl::ROLE_USER, Tools_Security_Acl::RESOURCE_THEMES);

$acl->allow(Tools_Security_Acl::ROLE_ADMIN);
$acl->deny(Tools_Security_Acl::ROLE_ADMIN, Tools_Security_Acl::RESOURCE_CODE);
$acl->deny(Tools_Security_Acl::ROLE_ADMIN, Tools_Security_Acl::RESOURCE_CACHE_PAGE);

$acl->allow(Tools_Security_Acl::ROLE_SUPERADMIN);
$acl->deny(Tools_Security_Acl::ROLE_SUPERADMIN, Tools_Security_Acl::RESOURCE_CACHE_PAGE);

Zend_Registry::set('acl', $acl);


$_SERVER['DOCUMENT_ROOT'] = rtrim($websiteConfig['path'], "/");
$_SERVER['SCRIPT_FILENAME'] = $websiteConfig['path'] . 'index.php';
$_SERVER['HTTP_HOST'] = '';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['QUERY_STRING'] = '';
$_SERVER['SERVER_NAME'] = '';
$_SERVER['HTTP_HOST'] = rtrim($websiteConfig['url'], '/');
$_SERVER['HTTP_ORIGIN'] = 'http://' . $_SERVER['HTTP_HOST'];
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';


Zend_Controller_Front::getInstance()->registerPlugin(new Plugins_Plugin());
Zend_Controller_Front::getInstance()->setRequest($request);
Zend_Controller_Front::getInstance()->setResponse(new Zend_Controller_Response_Http());


$filePath = $configIni->website->website->path;
$pluginIncludePath = array();
set_include_path(implode(PATH_SEPARATOR, $pluginIncludePath) . PATH_SEPARATOR . get_include_path());

$websiteConfig = $configIni->website->website->toArray();
$cacheFrontendOptions = $configIni->cache->cache->frontend->toArray();
$cacheBackendOptions = $configIni->cache->cache->backend->toArray();
$cacheBackendOptions['cache_dir'] = $websiteConfig['path'] . 'cache' . DIRECTORY_SEPARATOR;
$cache = Zend_Cache::factory('Core', 'File', $cacheFrontendOptions, $cacheBackendOptions);
Zend_Db_Table_Abstract::setDefaultMetadataCache($cache);
Zend_Registry::set('cache', $cache);

$cacheHelper = new Helpers_Action_Cache();
$cacheHelper->init();
Zend_Controller_Action_HelperBroker::addHelper($cacheHelper);

$pageHelper = new Helpers_Action_Page();
$pageHelper->init();
Zend_Controller_Action_HelperBroker::addHelper($pageHelper);

$configHelper = new Helpers_Action_Config();
$configHelper->init();
Zend_Controller_Action_HelperBroker::addHelper($configHelper);

Zend_Layout::startMvc();
Zend_Layout::getMvcInstance()->getLayoutPath();

set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path()
)));

if (!Zend_Registry::isRegistered('Zend_Currency')) {
    $locale = Zend_Locale::getLocaleToTerritory('US');
    try {
        $currency = new Zend_Currency(
            'USD',
            $locale
        );
    } catch (Zend_Currency_Exception $e) {
        error_log($e->getMessage());
        error_log($e->getTraceAsString());
        $currency = new Zend_Currency();
    }
    Zend_Registry::set('Zend_Currency', $currency);
}


$listOfPlugins = array(
    'api',
    'shopping',
    'cart',
    'quote',
    'apps',
    'crm',
    'toasterstats',
    'dashboard',
    'delivery',
    'emailsequence',
    'flatrateshipping',
    'invoicetopdf',
    'leads',
    'netcontent',
    'newslog',
    'notifier',
    'paypal',
    'productonly',
    'promo',
    'socialposter',
    'toastauth',
    'webbuilder',
    'widcard'
);

$pluginMapper = Application_Model_Mappers_PluginMapper::getInstance();

foreach ($listOfPlugins as $pluginName) {
    $plugin = Application_Model_Mappers_PluginMapper::getInstance()->findByName($pluginName);
    if ($plugin instanceof Application_Model_Models_Plugin) {
        continue;
    }

    $plugin = Tools_Plugins_Tools::findPluginByName($pluginName);
    if ($plugin->getStatus() === Application_Model_Models_Plugin::ENABLED) {
        continue;
    }

    $pluginId = intval($pluginMapper->save($plugin, false));
    $statusFile = Application_Model_Models_Plugin::INSTALL_FILE_NAME;
    $observerAction = Tools_Plugins_GarbageCollector::CLEAN_ONCREATE;

    $sqlFilePath = $websiteConfig['path'] . $misc['pluginsPath'] . $plugin->getName() . '/system/' . $statusFile;
    if (!file_exists($sqlFilePath)) {
        continue;
    }

    try {
        $sqlFileContent = Tools_Filesystem_Tools::getFile($sqlFilePath);
        if (strlen($sqlFileContent)) {
            $queries = Tools_System_SqlSplitter::split($sqlFileContent);
            if (is_array($queries) && !empty ($queries)) {
                $dbAdapter = Zend_Registry::get('dbAdapter');
                try {
                    array_walk($queries, function ($query) use ($dbAdapter) {
                        if (strlen(trim($query))) {
                            $dbAdapter->query($query);
                        }
                    });
                } catch (Exception $e) {
                    error_log($e->getMessage());
                    $pluginMapper->deleteByName($plugin);
                    $this->_helper->response->fail($e->getMessage());
                }
            }
        }
    } catch (Exceptions_SeotoasterPluginException $se) {
        error_log($se->getMessage());
        $this->_helper->response->fail($se->getMessage());
    }

    $plugin->registerObserver(
        new Tools_Plugins_GarbageCollector(
            array('action' => $observerAction)
        )
    );

    if ($plugin->getStatus() == Application_Model_Models_Plugin::DISABLED && $plugin->getId() == null) {
        $pluginData = $pluginMapper->getPluginDataById($pluginId);
        if (!empty($pluginData)) {
            $plugin->setTags($pluginData['tags']);
            $plugin->setVersion($pluginData['version']);
        }
        $plugin->setId($pluginId);
        $plugin->setStatus(Application_Model_Models_Plugin::ENABLED);
        $pluginMapper->save($plugin);
    }
}

echo 'Done!';

