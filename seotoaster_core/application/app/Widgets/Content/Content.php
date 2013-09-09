<?php

class Widgets_Content_Content extends Widgets_AbstractContent {

	protected function  _init() {
		$this->_type    = (isset($this->_options[1]) && $this->_options[1] == 'static') ? Application_Model_Models_Container::TYPE_STATICCONTENT : Application_Model_Models_Container::TYPE_REGULARCONTENT;
		parent::_init();
        $this->_view = new Zend_View(array(
            'scriptPath' => dirname(__FILE__) . '/views'
        ));
        $website = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
        $this->_view->websiteUrl = $website->getUrl();
//		$this->_name    = $this->_options[0];
//		$this->_pageId  = ($this->_type == Application_Model_Models_Container::TYPE_STATICCONTENT) ? 0 : $this->_toasterOptions['id'];
        $contentId = implode('_', $this->_options) . '_pid_'. $this->_pageId; // $this->_name . $this->_pageId . $this->_type;
		$this->_cacheId = $contentId . Zend_Controller_Action_HelperBroker::getStaticHelper('Session')->getCurrentUser()->getRoleId();
	}

	protected function  _load() {
        if(end($this->_options) == 'ajax') {
            $this->_view->type = $this->_type;
            $this->_view->name = $this->_options[0];
            $this->_view->pageId = $this->_pageId;
            return $this->_view->render('ajax.phtml');
        }
		$this->_content  = Application_Model_Mappers_ContainerMapper::getInstance()->findByName($this->_name, $this->_pageId, $this->_type);
		$contentContent  = (null === $this->_content) ? '' : $this->_content->getContent();
		if(Tools_Security_Acl::isAllowed($this)) {
			//$contentContent = ($this->_checkPublished()) ? $contentContent : '<div style="border: 1px dashed red">' . $contentContent . '</div>';
			$contentContent .= $this->_addAdminLink($this->_type, ($this->_content === null) ? null : $this->_content->getId(), 'Click to edit content', 964, 594);
			if ((bool)Zend_Controller_Action_HelperBroker::getExistingHelper('config')->getConfig('inlineEditor')){
				$contentContent = '<div class="container-wrapper '. ($this->_checkPublished() ? '' : 'unpublished') .'">'.$contentContent.'</div>';
			} elseif(!$this->_checkPublished()) {
				$contentContent = '<div class="unpublished">'.$contentContent.'</div>';
			}
		}
		else {
			$contentContent = ($this->_checkPublished()) ? $contentContent : '';
		}
		return $contentContent;
	}

	/**
	 * Checks if content published
	 * @return bool true if published
	 */
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

