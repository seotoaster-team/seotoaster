<?php

class Helpers_Action_Admin extends Zend_Controller_Action_Helper_Abstract {

	private $_view  = null;

	private $_cache = null;

	public function init() {
		$this->_cache = Zend_Controller_Action_HelperBroker::getStaticHelper('Cache');
		$this->_view  = $this->_actionController->view;
	}

	public function renderAdminPanel($userRole = null) {
        if ($userRole === Tools_Security_Acl::ROLE_MEMBER && (bool) Zend_Controller_Action_HelperBroker::getStaticHelper('config')->getConfig('controlPanelStatus')) {
            return;
        }
        $websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
		$userRole = preg_replace('/[^\w\d_]/', '', $userRole);
		if(!$additionalMenu = $this->_cache->load('admin_addmenu', $userRole)) {
			$additionalMenu = Tools_Plugins_Tools::fetchPluginsMenu($userRole);
			$this->_cache->save('admin_addmenu', $additionalMenu, $userRole, array(), '7200');
		}
		$this->_view->additionalMenu = $additionalMenu;
        if($this->_view->placeholder('logoSource')->getValue() == array()) {
            $this->_view->placeholder('logoSource')->set($websiteHelper->getUrl() . 'system/images/cpanel-img.jpg');
        }
        return $this->_view->render('admin/adminpanel.phtml');
	}



}

