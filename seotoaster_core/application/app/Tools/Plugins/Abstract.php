<?php

/**
 * Abstract plugin class. Contains all init parameters,
 *
 * Options, Dispatchers, Optionmakers, etc...
 * @property $emailTriggers   array|null List of email triggers-watchers pairs
 * @property $emailRecipients array|null List of plugin specific email recivers
 */

abstract class Tools_Plugins_Abstract implements Interfaces_Plugin {

	/**
	 * @const   ACTION_POSTFIX  Method which name ends with this postfix will be dispatched as action
	 */
	const ACTION_POSTFIX = 'Action';

	/**
	 * @const   OPTION_MAKER_PREFIX Method which name begins with this prefix will be dispatched as content generator
	 */
	const OPTION_MAKER_PREFIX = '_makeOption';

	/**
	 * Name of the placeholder in toaster layout where plugins can inject own content
	 */
	const INJECT_PLACEHOLDER = 'plugins';

	/**
	 * Options for plugin
	 *
	 * @var array
	 */
	protected $_options = array();

	/**
	 * All data received from Seotoaster.
	 *
	 * Contains at least website url.
	 * @var array
	 */
	protected $_seotoasterData = array();

	/**
	 * Seotoaster session helper
	 *
	 * @var Helpers_Action_Session
	 */
	protected $_sessionHelper = null;

	/**
	 * Plugin view.
	 *
	 * By default view scripts
	 * directory is set to plugin_directory/views/
	 * @var Zend_View
	 */
	protected $_view = null;

	/**
	 * Seotoaster website url
	 *
	 * @var string
	 */
	protected $_websiteUrl = null;

	/**
	 * Request object
	 *
	 * @var Zend_Controller_Request_Http
	 */
	protected $_request = null;

	/**
	 * Response object
	 *
	 * @var Zend_Controller_Response_Http
	 */
	protected $_response = null;

	/**
	 * Redirector helper
	 *
	 * @var Zend_Controller_Action_Helper_Redirector
	 */
	protected $_redirector = null;

	/**
	 * Translator
	 *
	 * @var Zend_Translate
	 */
	protected $_translator = null;

	/**
	 * Path to plugin's languages files
	 *
	 * @var string
	 */
	protected $_languagesPath = 'system/languages/';

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
	protected $_responseHelper = null;

	/**
	 * Access control list binding
	 *
	 * @var array List of ROLE => actions pairs
	 */
	protected $_securedActions = array();

	/**
	 * Website helper
	 *
	 * @var null|Helpers_Action_Website
	 */
	protected $_websiteHelper = null;

	protected $_pluginName = '';

	public function  __construct($options, $seotoasterData) {
		// setting up Seotoaster data and plugin options
		$this->_options = $options;
		$this->_seotoasterData = $seotoasterData;

		// setting up helpers
		$this->_sessionHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('session');
		$this->_websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
		$this->_responseHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('response');
		$this->_redirector = new Zend_Controller_Action_Helper_Redirector();

		// setting up request and response objects
		$front = Zend_Controller_Front::getInstance();
		$this->_request = $front->getRequest();
		$this->_response = $front->getResponse();
		unset($front);

        // init translator
        $this->_translator = Zend_Registry::get('Zend_Translate');

		// setting up view
		$this->_view = new Zend_View();
		$this->_websiteUrl = $this->_websiteHelper->getUrl();
		$this->_pluginName = strtolower(__CLASS__);
		$this->_view->websiteUrl = $this->_websiteUrl;
		$this->_view->pluginName = $this->_pluginName;
        $this->_view->secureToken = Tools_System_Tools::initSecureToken(get_called_class() . 'Token');

		// setting up view helpers (standart and ZendX)
		$this->_view->setHelperPath(APPLICATION_PATH . '/views/helpers/');
		$this->_view->addHelperPath('ZendX/JQuery/View/Helper/', 'ZendX_JQuery_View_Helper');

		// runing init routines
		$this->_initAcl();
		$this->_init();
	}

	protected function _init() {

	}

    /**
     * @deprecated
     */
    protected function _initTranslator() {
		$this->_translator = Zend_Registry::get('Zend_Translate');
		$langsPath = $this->_websiteHelper->getPath() . 'plugins/' . strtolower(get_called_class()) . '/' . $this->_languagesPath;
		if (is_dir($langsPath) && is_readable($langsPath)) {
			$locale = Zend_Registry::get('Zend_Locale');
			if (!file_exists($langsPath . $locale->getLanguage() . '.lng')) {
				if (Tools_System_Tools::debugMode()) {
					error_log('Language file ' . $locale->getLanguage() . '.lng does not exist');
				}
				return false;
			}
			try {
				$this->_translator->addTranslation(array(
					'content' => $langsPath . $locale->getLanguage() . '.lng',
					'locale'  => $locale->getLanguage(),
                    'reload' => true
				));
                Zend_Registry::set('Zend_Translate', $this->_translator);
			} catch (Exception $e) {
				if (Tools_System_Tools::debugMode()) {
					error_log("(plugin: " . strtolower(get_called_class()) . ") " . $e->getMessage() . "\n" . $e->getTraceAsString());
				}
			}
		}
	}

	/**
	 * Access control list initializing
	 */
	protected function _initAcl() {
		if (!empty($this->_securedActions)) {
			$acl = Zend_Registry::get('acl');
			foreach ($this->_securedActions as $role => $actions) {
				if (is_array($actions) && !empty($actions)) {
					foreach ($actions as $action) {
						$resource = new Zend_Acl_Resource(get_called_class() . '-' . $action);
						if (!$acl->has($resource)) {
							$acl->addResource($resource);
						}
						$acl->allow($role, $resource);
					}
				}
			}
			Zend_Registry::set('acl', $acl);
		}
	}

	public function run($requestedParams = array()) {
		$this->_requestedParams = $requestedParams;
		$optionResult = $this->_dispatchOptions();
		if ($optionResult) {
			return $optionResult;
		}
		$this->_dispatch($requestedParams);
	}

	protected function _dispatchOptions() {
		if (!empty($this->_options)) {
			foreach ($this->_options as $option) {
				$optionMakerName = self::OPTION_MAKER_PREFIX . ucfirst($option);
				if (in_array($optionMakerName, get_class_methods($this))) {
					return $this->$optionMakerName();
				}
				//return '<strong>Invalid option</strong>';
				return $option;
			}
		}
	}

	/**
	 * Inject plugin's content into the toaster layout
	 *
	 * @param string $content
	 */
	protected function _injectContent($content) {
		Zend_Layout::getMvcInstance()->getView()->placeholder(self::INJECT_PLACEHOLDER)->append($content);
	}

	private function _dispatch($params) {
		$action = (isset($params['run'])) ? $params['run'] . self::ACTION_POSTFIX : null;
		if ($action !== null) {
			if (in_array($action, get_class_methods($this))
					&& Tools_Security_Acl::isAllowed(get_called_class() . '-' . $params['run'])
			) {
				$this->$action();
				exit;
			}
		}
	}

	public function getRoleId() {
		return Tools_Security_Acl::ROLE_SYSTEM;
	}
}

