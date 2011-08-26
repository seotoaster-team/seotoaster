<?php

class Widgets_Content_Content extends Widgets_AbstractContent {

	private $_acl     = null;

	private $_type    = null;

	private $_pageId  = null;

	private $_name    = null;

	private $_content = null;

	protected function  _init() {
		parent::_init();
		$this->_name    = $this->_options[0];
		$this->_type    = (isset($this->_options[1]) && $this->_options[1] == 'static') ? Application_Model_Models_Container::TYPE_STATICCONTENT : Application_Model_Models_Container::TYPE_REGULARCONTENT;
		$this->_pageId  = ($this->_type == Application_Model_Models_Container::TYPE_STATICCONTENT) ? 0 : $this->_toasterOptions['id'];
		$this->_cacheId = $this->_name . $this->_pageId . $this->_type;
	}

	protected function  _load() {
		$currentUser     = Zend_Controller_Action_HelperBroker::getStaticHelper('Session')->getCurrentUser();
		$this->_content  = Application_Model_Mappers_ContainerMapper::getInstance()->findByName($this->_name, $this->_pageId, $this->_type);
		$contentContent  = (null === $this->_content) ? '' : $this->_content->getContent();
		if(Tools_Security_Acl::isAllowed($this, $currentUser)) {
			$contentContent = ($this->_checkPublished()) ? $contentContent : '<div style="border: 1px solid red">' . $contentContent . '</div>';
			$contentContent .= $this->_addAdminLink($this->_type, ($this->_content === null) ? null : $this->_content->getId(), 'Click to edit content', 960, 650);
		}
		else {
			$contentContent = ($this->_checkPublished()) ? $contentContent : '';
		}
		return $contentContent;
	}

	private function _checkPublished() {
		if($this->_content !== null) {
			if(!$this->_content->getPublished()) {
				if($this->_content->getPublishingDate()) {
					$zDate = new Zend_Date();
					$result = $zDate->compare(strtotime($this->_content->getPublishingDate()));
					if($result == 0 || $result == 1) {
						$this->_content->setPublishingDate('');
						$this->_content->setPublished(true);
						Application_Model_Mappers_ContainerMapper::getInstance()->save($this->_content);
					}
				}
			}
			return $this->_content->getPublished();
		}
		return true;
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

