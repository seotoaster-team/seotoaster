<?php
/**
 * Header widget
 *
 * @author iamne
 */
class Widgets_Header_Header extends Widgets_AbstractContent {

	protected function  _init() {
		parent::_init();
		$this->_acl = Zend_Registry::get('acl');
		$this->_name    = $this->_options[0];
		$this->_type    = (isset($this->_options[1]) && $this->_options[1] == 'static') ? Application_Model_Models_Container::TYPE_STATICHEADER : Application_Model_Models_Container::TYPE_REGULARHEADER;
		$this->_pageId  = ($this->_type == Application_Model_Models_Container::TYPE_STATICHEADER) ? 0 : $this->_toasterOptions['id'];
		$this->_cacheId = $this->_name . $this->_pageId . $this->_type;
	}

	protected function  _load() {
		$currentUser = Zend_Controller_Action_HelperBroker::getStaticHelper('Session')->getCurrentUser();
		$header      = Application_Model_Mappers_ContainerMapper::getInstance()->findByName($this->_name, $this->_pageId, $this->_type);
		$headerContent = (null === $header) ? '' : $header->getContent();
		if($this->_acl->isAllowed($currentUser, $this)) {
			$headerContent .= $this->_addAdminLink($this->_type, (!$headerContent) ? null : $header->getId(), 'Click to edit header', 600, 170);
		}
		return $headerContent;
	}

	/**
	 * Overrides abstract class method
	 * For Header and Content widgets we have a different resource id
	 *
	 * @return string ACL Resource id
	 */
	public function  getResourceId() {
		return Tools_Security_Acl::RESOURCE_CONTENT;
	}
}

