<?php

/**
 * Code
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Widgets_Code_Code extends Widgets_AbstractContent {

	protected function  _init() {
		parent::_init();
		$this->_type    = Application_Model_Models_Container::TYPE_CODE;
		$this->_acl     = Zend_Registry::get('acl');
		$this->_name    = $this->_options[0];
		$this->_pageId  = $this->_toasterOptions['id'];
		$this->_cacheId = $this->_name . $this->_pageId;
	}

	protected function _load() {
		if(!is_array($this->_options) || empty($this->_options) || !isset($this->_options[0]) || !$this->_options[0] || preg_match('~^\s*$~', $this->_options[0])) {
			throw new Exceptions_SeotoasterException($this->_translator->translate('You should specify code container name.'));
		}
		$currentUser = Zend_Controller_Action_HelperBroker::getStaticHelper('Session')->getCurrentUser();
		$code        = Application_Model_Mappers_ContainerMapper::getInstance()->findByName($this->_name, $this->_pageId, $this->_type);
		$codeContent = (null === $code) ? '' : $code->getContent();

		if(!preg_match('~<script~', $codeContent)) {
			ob_start();
			$returned    = eval($codeContent);
			$codeContent = ob_get_clean();
			ob_get_flush();
			$codeContent .= $returned;
		}

		if($this->_acl->isAllowed($currentUser, $this)) {
			$codeContent .= $this->_addAdminLink($this->_type, (!$codeContent) ? null : $code->getId(), $this->_translator->translate('Click to edit header'), 480, 650);
		}

		return $codeContent;
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

