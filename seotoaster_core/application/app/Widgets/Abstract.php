<?php

/**
 * Description of Abstract
 *
 * @author iamne
 */
abstract class Widgets_Abstract  implements Zend_Acl_Resource_Interface {

    /**
     * Instance of Zend_View
     *
     * @var Zend_View
     */
    protected $_view           = null;

	protected $_options        = null;

	protected $_toasterOptions = null;

	protected $_cache          = null;

	protected $_cacheId        = null;

	protected $_cachePrefix    = 'widget_';

	protected $_cacheable      = true;

	protected $_cacheTags      = array();

    protected $_cacheLifeTime  = Helpers_Action_Cache::CACHE_WEEK;

    /**
     * Instance of the Zend_Translate
     *
     * @var mixed|Zend_Translate
     */
    protected $_translator     = null;

	public function  __construct($options = null, $toasterOptions = array()) {
		$this->_options        = $options;
		$this->_toasterOptions = $toasterOptions;
		if($this->_cacheable === true) {
			$this->_cache = Zend_Controller_Action_HelperBroker::getStaticHelper('Cache');
			$this->_cacheId   = strtolower(get_called_class()).(!empty($this->_options)?'-'.implode('-', $this->_options):'');
			if(isset($toasterOptions['id'])) {
                $this->_cacheId .= '_pid-'.$toasterOptions['id'];
            }
			$this->_cacheId .= '_'.Zend_Controller_Action_HelperBroker::getStaticHelper('Session')->getCurrentUser()->getRoleId();
        }
		$this->_translator = Zend_Registry::get('Zend_Translate');
		$this->_init();
	}

	protected function _init() {

	}

	public function  getResourceId() {
		return Tools_Security_Acl::RESOURCE_WIDGETS;
	}


	public function render() {
		$content = null;
		if($this->_cacheable) {
			if(null === ($content = $this->_loadFromCache())) {
				try {
					$content = $this->_load();
					$this->_cache->save(
                        $this->_cacheId,
                        $content, $this->_cachePrefix,
                        is_array($this->_cacheTags) ? $this->_cacheTags : array(),
                        $this->_cacheLifeTime
                    );
				}
				catch (Exceptions_SeotoasterException $ste) {
					$content = $ste->getMessage();
				}
			}
		}
		else {
			$content = $this->_load();
		}
		return $content;
	}

	protected function _loadFromCache() {
		return $this->_cache->load($this->_cacheId, $this->_cachePrefix);
	}

	abstract protected function _load();
}

