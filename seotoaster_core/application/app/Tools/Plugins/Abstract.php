<?php

/**
 * Abstract plugin class. Contains all init parameters,
 *
 * Options, Dispatchers, Optionmakers, etc...
 */

class Tools_Plugins_Abstract implements Interfaces_Plugin {

	const ACTION_POSTFIX        = 'Action';

	/**
	 * Options for plugin
	 *
	 * @var array
	 */
	protected $_options         = array();

	/**
	 * All data received from Seotoaster.
	 *
	 * Contains at least website url.
	 * @var array
	 */
	protected $_seotoasterData  = array();

	/**
	 * Seotoaster session
	 *
	 * @var Zend_Session_Namespace
	 */
	protected $_session         = null;

	/**
	 * Plugin view.
	 *
	 * By default view scripts
	 * directory is set to plugin_directory/views/
	 * @var Zend_View
	 */
	protected $_view            = null;

	/**
	 * Seotoaster website url
	 *
	 * @var string
	 */
	protected $_websiteUrl      = null;

	/**
	 * Request object
	 *
	 * @var Zend_Controller_Request_Http
	 */
	protected $_request         = null;

	/**
	 * Response object
	 *
	 * @var Zend_Controller_Response_Http
	 */
	protected $_response        = null;

	/**
	 * Redirector helper
	 *
	 * @var Zend_Controller_Action_Helper_Redirector
	 */
	protected $_redirector      = null;

	/**
	 * Translator
	 *
	 * @var Zend_Translate
	 */
	protected $_translator      = null;

	/**
	 * Parameters that has been passed to the plugin
	 *
	 * @var array
	 */
	protected $_requestedParams = array();

	/**
	 * Toaster response helper
	 *
	 * @var Helpers_Action_Response
	 */
	protected $_responseHelper  = null;

	public function  __construct($options, $seotoasterData) {
		$this->_options          = $options;
		$this->_seotoasterData   = $seotoasterData;
		$this->_websiteUrl       = isset($this->_seotoasterData['websiteUrl']) ? $this->_seotoasterData['websiteUrl'] : '';
		$this->_request          = new Zend_Controller_Request_Http();
		$this->_response         = new Zend_Controller_Response_Http();
		$this->_responseHelper   = Zend_Controller_Action_HelperBroker::getStaticHelper('response');
		$this->_redirector       = new Zend_Controller_Action_Helper_Redirector();
		$this->_session          = Zend_Registry::get('session');
		$this->_view             = new Zend_View();
		$this->_view->websiteUrl = $this->_websiteUrl;
		$this->_view->setHelperPath(APPLICATION_PATH . '/views/helpers/');
		$this->_view->addHelperPath('ZendX/JQuery/View/Helper/', 'ZendX_JQuery_View_Helper');
		$this->_translator       = Zend_Registry::get('Zend_Translate');
		$this->_init();
	}

	protected function _init() {

	}

	public function run($requestedParams = array()) {
		$this->_requestedParams = $requestedParams;
		$optionResult           = $this->_dispatchOptions();
		if($optionResult) {
			return $optionResult;
		}
		$this->_dispatch($requestedParams);
	}

	protected function _dispatchOptions() {
		if(!empty($this->_options)) {
			foreach ($this->_options as $option) {
				$optionMakerName = '_makeOption' . ucfirst($option);
				if(in_array($optionMakerName, get_class_methods($this))) {
					return $this->$optionMakerName();
				}
				//return '<strong>Invalid option</strong>';
				return $option;
			}
		}
	}

	private function _dispatch($params) {
		$action = (isset($params['run'])) ? $params['run'] . self::ACTION_POSTFIX : null;
		if($action !== null) {
			if(in_array($action, get_class_methods($this))) {
				$this->$action();
				exit;
			}
		}
	}

	public function getRoleId() {
		return Tools_Security_Acl::ROLE_SYSTEM;
	}
}

