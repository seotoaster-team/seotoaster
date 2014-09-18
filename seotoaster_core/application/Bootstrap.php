<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{

    private $_configIni = null;

    protected function _initDoctype()
    {
        $this->bootstrap('view');
        $view = $this->getResource('view');
        $view->doctype('HTML5');
    }

    protected function _initConfig()
    {
        $this->_configIni = new Zend_Config_Ini(APPLICATION_PATH . '/configs/' . SITE_NAME . '.ini');
        Zend_Registry::set('website', $this->_configIni->website->website->toArray());
        Zend_Registry::set('database', $this->_configIni->database->database->toArray());
        Zend_Registry::set('theme', $this->_configIni->theme->theme->toArray());
        Zend_Registry::set('news', $this->_configIni->news->news->toArray());
        Zend_Registry::set('misc', $this->_configIni->misc->misc->toArray());
    }

    protected function _initIncludePath()
    {
        set_include_path(realpath(APPLICATION_PATH . '/app') . PATH_SEPARATOR . get_include_path());
    }

    protected function _initAutoload()
    {
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
    }

    protected function _initRoutes()
    {
        $routesXmlPath = is_file(
            APPLICATION_PATH . '/configs/' . SITE_NAME . '.routes.xml'
        ) ? APPLICATION_PATH . '/configs/' . SITE_NAME . '.routes.xml' : APPLICATION_PATH . '/configs/routes.xml';
        $routes = new Zend_Config_Xml($routesXmlPath);
        $router = Zend_Controller_Front::getInstance()->getRouter();
        $router->addConfig($routes, 'routes');
    }

    protected function _initDatabase()
    {
//		$config   = new Zend_Config_Ini(APPLICATION_PATH . '/configs/' . SITE_NAME . '.ini', 'database');
        $dbConfig = $this->_configIni->database->database->toArray();
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
    }

    protected function _initSession()
    {
        $session = new Zend_Session_Namespace('toaster_' . SITE_NAME, true);
        Zend_Registry::set('session', $session);
    }

    protected function _initCache()
    {
        $cacheFrontendOptions = $this->_configIni->cache->cache->frontend->toArray();
        $cacheBackendOptions = $this->_configIni->cache->cache->backend->toArray();
        $cache = Zend_Cache::factory('Core', 'File', $cacheFrontendOptions, $cacheBackendOptions);
        Zend_Db_Table_Abstract::setDefaultMetadataCache($cache);
        Zend_Registry::set('cache', $cache);
    }

    protected function _initHelpers()
    {
        Zend_Controller_Action_HelperBroker::addHelper(new Helpers_Action_Page());
        Zend_Controller_Action_HelperBroker::addHelper(new Helpers_Action_Cache());
        Zend_Controller_Action_HelperBroker::addHelper(new Helpers_Action_Session());
        Zend_Controller_Action_HelperBroker::addHelper(new Helpers_Action_Admin());
        Zend_Controller_Action_HelperBroker::addHelper(new Helpers_Action_Config());
        Zend_Controller_Action_HelperBroker::addHelper(new Helpers_Action_Website());
        Zend_Controller_Action_HelperBroker::addHelper(new Helpers_Action_Response());
        Zend_Controller_Action_HelperBroker::addHelper(new Helpers_Action_Language());
        Zend_Controller_Action_HelperBroker::addHelper(new Helpers_Action_Mobile());
    }

    protected function _initPlugins()
    {
        Zend_Controller_Front::getInstance()->registerPlugin(new Plugins_Plugin());
    }

    protected function _initAcl()
    {
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
    }

    protected function _initLocale()
    {
        $config = Application_Model_Mappers_ConfigMapper::getInstance()->getConfig();
        $name = Zend_Locale::getLocaleToTerritory($config['language']);
        if ($name !== null) {
            $locale = new Zend_Locale($name);
        } else {
            $locale = new Zend_Locale();
        }
        $locale->setCache(Zend_Registry::get('cache'));

        Zend_Registry::set('Zend_Locale', $locale);
    }

    protected function _initTranslator()
    {
        $session = Zend_Registry::get('session');

        $locale = (isset($session->locale)) ? $session->locale : Zend_Registry::get('Zend_Locale');

        $session->locale = $locale;

        $translator = new Zend_Translate(array(
            'adapter' => 'array',
            'content' => 'system/languages/',
            'scan'    => Zend_Translate::LOCALE_FILENAME,
            'locale'  => $locale->getLanguage(),
            'ignore'  => array('.'),
            'route'   => array('fr' => 'en', 'it' => 'en', 'de' => 'en'),
            'cache'   => Zend_Registry::get('cache')
        ));

//		Zend_Form::setDefaultTranslator($translator);
        Zend_Registry::set('Zend_Locale', $locale);
        Zend_Registry::set('Zend_Translate', $translator);
        Zend_Registry::set('session', $session);
    }

    protected function _initZendX()
    {
        $view     = new Zend_View();
        $website  = Zend_Registry::get('website');
        $misc     = Zend_Registry::get('misc');
        $url      = preg_replace('~^https?://~', '', $website['url']);
        $request  = new Zend_Controller_Request_Http();
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
    }

    protected function _initObserversQueue()
    {
        $observerQueue = array();
        $observersDbTable = new Application_Model_DbTable_ObserversQueue();
        $data = $observersDbTable->fetchAll()->toArray();
        if (sizeof($data)) {
            foreach ($data as $row) {
                $observable = $row['observable'];
                $observer = $row['observer'];

                if (!array_key_exists($observable, $observerQueue)) {
                    $observerQueue[$observable] = array();
                }

                array_push($observerQueue[$observable], $observer);
                unset($observable, $observer);
            }
        }

        Zend_Registry::set('observers_queue', $observerQueue);
    }

    protected function _initDbProfiler()
    {
        if (APPLICATION_ENV === 'development') {
            if (isset($_GET['_profileSql'])) {
                setcookie('_profileSql', $_GET['_profileSql']);
            }
            if (isset($_COOKIE['_profileSql'])) {
                $profiler = new Zend_Db_Profiler();
                $profiler->setEnabled(true);
                Zend_Db_Table_Abstract::getDefaultAdapter()->setProfiler($profiler);
                register_shutdown_function(array('Tools_System_Tools', 'sqlProfiler'));
            }
        }
    }

    /**
     * Initialize (injects) Seotoaster parser into the registry
     *
     * This feature makes Seotoaster more flexible -
     * Now there is way to extend the parser without pain in the neck
     *
     * @author Eugene I. Nezhuta <eugene.nezhuta@gmail.com>
     */
    protected function _initParser()
    {
        Zend_Registry::set(
            'Toaster_Parser',
            function ($content = null, $data = null, $options = null) {
                return new Tools_Content_Parser($content, $data, $options);
            }
        );
    }
}

