<?php

/**
 * Code
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Widgets_Code_Code extends Widgets_AbstractContent {

	protected function  _init() {
		$this->_type    = Application_Model_Models_Container::TYPE_CODE;
		parent::_init();
		$this->_cacheId = $this->_name . $this->_pageId;
        $this->_cacheable = false;
	}

	protected function _load() {
		if(!$this->_checkEnabled()) {
			return '';
		}
		if(!is_array($this->_options) || empty($this->_options) || !isset($this->_options[0]) || !$this->_options[0] || preg_match('~^\s*$~', $this->_options[0])) {
			throw new Exceptions_SeotoasterException($this->_translator->translate('You should specify code container name.'));
		}
		$this->_container = Application_Model_Mappers_ContainerMapper::getInstance()->findByName($this->_name, $this->_pageId, $this->_type);
		$codeContent      = (null === $this->_container) ? '' : $this->_container->getContent();

		if(!preg_match('~<script~', $codeContent)) {
			ob_start();
			$returned    = eval($codeContent);
			$codeContent = ob_get_clean();
			ob_get_flush();
			$codeContent .= $returned;
		}
        if(Tools_Security_Acl::isAllowed($this)) {
            $codeContent .= $this->_generateAdminControl(960, 560);
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

	private function _checkEnabled() {
		$configHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('config');
		return (boolean)$configHelper->getConfig('codeEnabled');
	}
}

