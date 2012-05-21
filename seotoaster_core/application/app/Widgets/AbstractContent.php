<?php
abstract class Widgets_AbstractContent extends Widgets_Abstract {

	protected $_acl     = null;

	protected $_type    = null;

	protected $_pageId  = null;

	protected $_name    = null;

	protected $_content = null;

	protected function _addAdminLink($containerType, $containerId = null, $title = '', $width = 0, $height = 0) {
		$adminIconName = '';
		$imgAlt        = '';
		switch ($containerType) {
			case Application_Model_Models_Container::TYPE_REGULARCONTENT:
				$adminIconName = 'editadd.png';
				$imgAlt        = 'edit content';
			break;
			case Application_Model_Models_Container::TYPE_STATICCONTENT:
				$adminIconName = 'editadd-static-content.png';
				$imgAlt        = 'edit static content';
			break;
			case Application_Model_Models_Container::TYPE_REGULARHEADER:
				$adminIconName = 'editadd-header.png';
				$imgAlt        = 'edit header';
			break;
			case Application_Model_Models_Container::TYPE_STATICHEADER:
				$adminIconName = 'editadd-static-header.png';
				$imgAlt        = 'edit static header';
			break;
			case Application_Model_Models_Container::TYPE_CODE:
				$adminIconName = 'editadd-code.png';
				$imgAlt        = 'edit code';
			break;
		}
		if(null == $containerId) {
			return '<a class="tpopup generator-links" data-pwidth="' . $width . '" data-pheight="' . $height . '" title="Click to ' . $imgAlt . '" href="javascript:;" data-url="' . $this->_toasterOptions['websiteUrl'] . 'backend/backend_content/add/containerType/' . $containerType . '/containerName/' . $this->_options[0] . '/pageId/' . $this->_toasterOptions['id'] . '" class="generator-links"><img width="26" height="26" src="' . $this->_toasterOptions['websiteUrl'] . 'system/images/' . $adminIconName . '" alt="'. $imgAlt .'" /></a>';
		}

		return '<a class="tpopup generator-links" data-pwidth="' . $width . '" data-pheight="' . $height . '" title="Click to ' . $imgAlt . '" href="javascript:;" data-url="'. $this->_toasterOptions['websiteUrl'] . 'backend/backend_content/edit/id/' . $containerId . '/containerType/' . $containerType . '"  class="generator-links"><img width="26" height="26" src="' . $this->_toasterOptions['websiteUrl'] . 'system/images/' . $adminIconName .'" alt="'. $imgAlt .'" /></a>';

		//return '<a title="' . $title . '" href="javascript:;" onclick="showToasterPopup(\''. $this->_toasterOptions['websiteUrl'] . 'backend/backend_content/edit/id/' . $containerId . '/containerType/' . $containerType . '/\')" class="generator-links"><img width="26" height="26" src="' . $this->_toasterOptions['websiteUrl'] . 'system/images/' . $adminIconName .'" alt="edit header" /></a>';
	}

	protected function _init() {
		parent::_init();
		array_push($this->_cacheTags, __CLASS__);
	}
}

