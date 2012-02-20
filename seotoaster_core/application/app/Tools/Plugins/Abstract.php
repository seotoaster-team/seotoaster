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
	 * Path to plugin's languages files
	 *
	 * @var string
	 */
	protected $_languagesPath   = 'system/languages/';

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

    /**
     * Access control list binding
     * @var array List of ROLE => actions pairs
     */
    protected $_securedActions = array();

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
		$this->_initAcl();
		$this->_initTranslator();
		$this->_init();
	}

	protected function _init() {

	}

	protected function _initTranslator() {
		$websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
		$this->_translator = Zend_Registry::get('Zend_Translate');
		$langsPath         = $websiteHelper->getPath() . 'plugins/' . strtolower(get_called_class()) . '/' . $this->_languagesPath;
		if(is_dir($langsPath) && is_readable($langsPath)) {
			$locale = Zend_Registry::get('Zend_Locale');
			if(!file_exists($langsPath . $locale->getLanguage() . '.lng')) {
				if(APPLICATION_ENV == 'development') {
					error_log('Language file ' . $locale->getLanguage() . '.lng does not exist');
				}
				return false;
			}
			try {
				$this->_translator->addTranslation(array(
					'content' => $langsPath . $locale->getLanguage() . '.lng',
					'locale'  => $locale,
			        'clear'   => true
				));
			} catch (Exception $e) {
				if(APPLICATION_ENV == 'development') {
					error_log($e->getMessage() . "\n" . $e->getTraceAsString());
				}
			}
		}
	}

    /**
     * Access control list initializing
     */
    protected function _initAcl() {
        if (!empty($this->_securedActions)){
            $acl = Zend_Registry::get('acl');
            foreach($this->_securedActions as $role => $actions){
                if (is_array($actions) && !empty($actions)){
                    foreach ($actions as $action){
                        $resource = new Zend_Acl_Resource(get_called_class().'-'.$action);
                        if (! $acl->has($resource)){
                            $acl->addResource($resource);
                            $acl->allow($role, $resource);
                        }
                    }
                }
            }
            Zend_Registry::set('acl', $acl);
        }
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
			if(in_array($action, get_class_methods($this))
                    && Tools_Security_Acl::isAllowed(get_called_class().'-'.$params['run'])) {
				$this->$action();
				exit;
			}
		}
	}

	public function getRoleId() {
		return Tools_Security_Acl::ROLE_SYSTEM;
	}
}

