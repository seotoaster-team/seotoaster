<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap {

	protected function _initDoctype() {
		$this->bootstrap('view');
		$view = $this->getResource('view');
		$view->doctype('HTML5');
	}

	protected function _initIncludePath() {
		set_include_path(realpath(APPLICATION_PATH . '/app') . PATH_SEPARATOR . get_include_path());
	}

	protected function _initAutoload() {
		$autoloader = Zend_Loader_Autoloader::getInstance();
		$autoloader->registerNamespace('Widgets_');
		$autoloader->registerNamespace('Interfaces_');
		$autoloader->registerNamespace('Helpers_');
		$autoloader->registerNamespace('Exceptions_');
		$autoloader->registerNamespace('Tools_');
		$autoloader->registerNamespace('Plugins_');
		$autoloader->setFallbackAutoloader(true);
	}

	protected function _initRoutes()  {
		$routest = new Zend_Config_Xml(APPLICATION_PATH . '/configs/' . SITE_NAME . 'routes.xml');
		$router  = Zend_Controller_Front::getInstance()->getRouter();
		$router->addConfig($routest, 'routes');
	}

	protected function _initDatabase() {
		$config   = new Zend_Config_Ini(APPLICATION_PATH . '/configs/' . SITE_NAME . 'application.ini', 'database');
		$database = Zend_Db::factory($config->database);
		Zend_Db_Table_Abstract::setDefaultAdapter($database);
		Zend_Registry::set('dbAdapter', $database);
	}

	protected function _initSession() {
		$config  = new Zend_Config_Ini(APPLICATION_PATH . '/configs/' . SITE_NAME . 'application.ini', 'website');
		$session = new Zend_Session_Namespace($config->website->url, true);
		Zend_Registry::set('session', $session);
	}

	protected function _initCache() {
		$config               = new Zend_Config_Ini(APPLICATION_PATH . '/configs/' . SITE_NAME . 'application.ini');
		$cacheFrontendOptions = $config->cache->cache->frontend->toArray();
		$cacheBackendOptions  = $config->cache->cache->backend->toArray();
		$cache = Zend_Cache::factory('Core', 'File', $cacheFrontendOptions, $cacheBackendOptions);
		Zend_Registry::set('cache', $cache);
	}

	protected function _initRegistry() {
		$config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/' . SITE_NAME . 'application.ini');
		Zend_Registry::set('website', $config->website->website->toArray());
		Zend_Registry::set('database', $config->database->database->toArray());
		Zend_Registry::set('theme', $config->theme->theme->toArray());
		Zend_Registry::set('news', $config->news->news->toArray());
		Zend_Registry::set('misc', $config->misc->misc->toArray());
	}

	protected function _initHelpers() {
		Zend_Controller_Action_HelperBroker::addHelper(new Helpers_Action_Page());
		Zend_Controller_Action_HelperBroker::addHelper(new Helpers_Action_Cache());
		Zend_Controller_Action_HelperBroker::addHelper(new Helpers_Action_Session());
		Zend_Controller_Action_HelperBroker::addHelper(new Helpers_Action_Admin());
		Zend_Controller_Action_HelperBroker::addHelper(new Helpers_Action_Config());
		Zend_Controller_Action_HelperBroker::addHelper(new Helpers_Action_Website());
		Zend_Controller_Action_HelperBroker::addHelper(new Helpers_Action_Response());
	}

	protected function _initPlugins() {
		$front = Zend_Controller_Front::getInstance();
		$front->registerPlugin(new Plugins_Acl());
	}

	protected function _initAcl() {
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

		//permissions
		$acl->allow(Tools_Security_Acl::ROLE_GUEST, Tools_Security_Acl::RESOURCE_PAGE_PUBLIC);
		$acl->allow(Tools_Security_Acl::ROLE_GUEST, Tools_Security_Acl::RESOURCE_CACHE_PAGE);

		$acl->deny(Tools_Security_Acl::ROLE_MEMBER, Tools_Security_Acl::RESOURCE_CACHE_PAGE);
		$acl->allow(Tools_Security_Acl::ROLE_MEMBER, Tools_Security_Acl::RESOURCE_PAGE_PROTECTED);

		$acl->allow(Tools_Security_Acl::ROLE_USER, Tools_Security_Acl::RESOURCE_CONTENT);

		$acl->allow(Tools_Security_Acl::ROLE_ADMIN);
		$acl->deny(Tools_Security_Acl::ROLE_ADMIN, Tools_Security_Acl::RESOURCE_USERS);
		$acl->deny(Tools_Security_Acl::ROLE_ADMIN, Tools_Security_Acl::RESOURCE_CODE);
		$acl->deny(Tools_Security_Acl::ROLE_ADMIN, Tools_Security_Acl::RESOURCE_CACHE_PAGE);

		$acl->allow(Tools_Security_Acl::ROLE_SUPERADMIN);
		$acl->deny(Tools_Security_Acl::ROLE_SUPERADMIN, Tools_Security_Acl::RESOURCE_CACHE_PAGE);

		Zend_Registry::set('acl', $acl);
	}

	protected function _initTranslator() {

		$session = Zend_Registry::get('session');
		$locale  = (isset($session->locale)) ? $session->locale : new Zend_Locale();
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

		Zend_Form::setDefaultTranslator($translator);
		Zend_Registry::set('Zend_Translate', $translator);
		Zend_Registry::set('session', $session);
	}

	protected function _initZendX() {
		$view    = new Zend_View();
		$website = Zend_Registry::get('website');
		$misc    = Zend_Registry::get('misc');
		$view->addHelperPath('ZendX/JQuery/View/Helper/', 'ZendX_JQuery_View_Helper');
		if($misc['jquery'] == 'local') {
			$view->jQuery()->setLocalPath($website['url'] . 'system/js/external/jquery/jquery.js');
		}
		else {
			$view->jQuery()->setVersion($misc['jqversion']);
		}
		if($misc['jqueryui'] == 'local') {
			$view->jQuery()->setUiLocalPath($website['url'] . 'system/js/external/jquery/jquery-ui.js');
		}
		else {
			$view->jQuery()->setUiVersion($misc['jquversion']);
		}

		$viewRenderer = new Zend_Controller_Action_Helper_ViewRenderer($view);
		Zend_Controller_Action_HelperBroker::addHelper($viewRenderer);
	}
}

