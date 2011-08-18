<?php

/**
 * Description of Abstract
 *
 * @author iamne
 */
abstract class Widgets_Abstract  implements Zend_Acl_Resource_Interface {

	protected $_view           = null;

	protected $_options        = null;

	protected $_toasterOptions = null;

	protected $_cache          = null;

	protected $_cacheId        = null;

	protected $_cachePrefix    = 'widget_';

	protected $_cacheable      = true;

	protected $_translator     = null;

	public function  __construct($options = null, $toasterOptions = array()) {
		$this->_options        = $options;
		$this->_toasterOptions = $toasterOptions;
		if($this->_cacheable === true) {
			$this->_cache = Zend_Controller_Action_HelperBroker::getStaticHelper('Cache');
			$this->_cacheId    = (!isset($this->_options[0])) ? strtolower(get_class($this)) : $this->_options[0];
			$this->_cacheId   .= implode('-', $this->_options);
			if(isset($toasterOptions['id'])) {
				$this->_cacheId .= $toasterOptions['id'];
			}
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
			if(null === ($content = $this->_loadFromCahce())) {
				try {
					$content = $this->_load();
					$this->_cache->save($this->_cacheId, $content, $this->_cachePrefix);
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

	protected function _loadFromCahce() {
		return $this->_cache->load($this->_cacheId, $this->_cachePrefix);
	}

	abstract protected function _load();
}

