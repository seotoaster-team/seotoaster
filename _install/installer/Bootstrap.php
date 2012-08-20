<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap {

	protected function _initDoctype() {
		$this->bootstrap('view');
		$view = $this->getResource('view');
		$view->doctype('HTML5');
	}

	protected function _initIncludePath() {
		set_include_path(realpath(APPLICATION_PATH . '/tools') . PATH_SEPARATOR . get_include_path());
	}
	
	protected function _initAutoload() {
		$autoloader = Zend_Loader_Autoloader::getInstance();
		$autoloader->setFallbackAutoloader(true);
	}

	protected function _initSession() {
		$session = new Zend_Session_Namespace('ToasterInstaller');
		Zend_Registry::set('session', $session);
	}

	protected function _initRegistry() {
		$config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/installer.ini');
		Zend_Registry::set('requirements', $config->requirements->toArray());
		Zend_Registry::set('database', $config->database->toArray());
	}

	protected function _initTranslator() {

		$session = Zend_Registry::get('session');

		Zend_Locale::setDefault('en');
		$locale  = (isset($session->locale)) ? $session->locale : new Zend_Locale(Zend_Locale::ZFDEFAULT);
		Zend_Registry::set('Zend_Locale', $locale);
		$session->locale = $locale;

		$translator = new Zend_Translate(array(
			'adapter' => 'array',
			'content' => INSTALL_PATH.DIRECTORY_SEPARATOR.'system/languages/',
			'scan'    => Zend_Translate::LOCALE_FILENAME,
			'locale'  => $locale->getLanguage(),
			'ignore'  => array('.'),
			'route'   => array('fr' => 'en', 'it' => 'en', 'de' => 'en')
		));
		
		Zend_Form::setDefaultTranslator($translator);
		Zend_Registry::set('Zend_Translate', $translator);
		Zend_Registry::set('session', $session);
	}

	protected function _initZendX() {
		$view    = new Zend_View();
		
		$view->addHelperPath('ZendX/JQuery/View/Helper/', 'ZendX_JQuery_View_Helper');
		$view->jQuery()->setVersion('1.7');
		$view->jQuery()->setUiVersion('1.8');

		$viewRenderer = new Zend_Controller_Action_Helper_ViewRenderer($view);
		Zend_Controller_Action_HelperBroker::addHelper($viewRenderer);
	}
}

