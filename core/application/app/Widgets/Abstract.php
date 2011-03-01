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
			if(isset($toasterOptions['id'])) {
				$this->_cacheId .= $toasterOptions['id'];
			}
		}
		$this->_translator = Zend_Registry::get('translator');
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

	protected function _addAdminLink($containerType, $containerId = null, $title = '', $width = 0, $height = 0) {
		$adminIconName = '';
		switch ($containerType) {
			case Application_Model_Models_Container::TYPE_REGULARCONTENT:
				$adminIconName = 'editadd.png';
			break;
			case Application_Model_Models_Container::TYPE_STATICCONTENT:
				$adminIconName = 'editadd-static-content.png';
			break;
			case Application_Model_Models_Container::TYPE_REGULARHEADER:
				$adminIconName = 'editadd-header.png';
			break;
			case Application_Model_Models_Container::TYPE_STATICHEADER:
				$adminIconName = 'editadd-static-header.png';
			break;
			case Application_Model_Models_Container::TYPE_CODE:
				$adminIconName = 'editadd-code.png';
			break;
		}
		if(null == $containerId) {
			return '<a title="' . $title . '" href="javascript:;" onclick="tb_show(\'\', \''. $this->_toasterOptions['websiteUrl'] . 'backend/backend_content/add/containerType/' . $containerType . '/containerName/' . $this->_options[0] . '/pageId/' . $this->_toasterOptions['id'] . '/?TB_iframe=true&height=' . $height .'&width=' . $width . '\')" class="generator-links"><img src="' . $this->_toasterOptions['websiteUrl'] . 'system/images/' . $adminIconName . '" alt="edit container" /></a>';
		}
		return '<a title="' . $title . '" href="javascript:;" onclick="tb_show(\'\', \''. $this->_toasterOptions['websiteUrl'] . 'backend/backend_content/edit/id/' . $containerId . '/containerType/' . $containerType . '/?TB_iframe=true&height=' . $height .'&width=' . $width . '\')" class="generator-links"><img src="' . $this->_toasterOptions['websiteUrl'] . 'system/images/' . $adminIconName .'" alt="edit header" /></a>';
	}

	abstract protected function _load();
}

