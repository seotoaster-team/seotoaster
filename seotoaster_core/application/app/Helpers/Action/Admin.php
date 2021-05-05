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
		$bottomsort = array();
		if(!$additionalMenu = $this->_cache->load('admin_addmenu', $userRole)) {
			$additionalMenu = Tools_Plugins_Tools::fetchPluginsMenu($userRole, true);

			if(!empty($additionalMenu['useSortParams'])) {
                asort($additionalMenu['useSortParams'], SORT_NUMERIC);
                $bottomsort = $additionalMenu['useSortParams'];
            }

			if(!empty($additionalMenu['additionalMenu'])) {
                $additionalMenu = $additionalMenu['additionalMenu'];
            }

			$this->_cache->save('admin_addmenu', $additionalMenu, $userRole, array(), '7200');
		}
		$this->_view->additionalMenu = $additionalMenu;
        $this->_view->bottomsort = $bottomsort;

        if($this->_view->placeholder('logoSource')->getValue() == array()) {
            $this->_view->placeholder('logoSource')->set($websiteHelper->getUrl() . 'system/images/cpanel-img.jpg');
        }
        $this->_view->userRole = $userRole;

        $configMapper = Application_Model_Mappers_ConfigMapper::getInstance();
        $toasterConfig = $configMapper->getConfig();

        $mojoCompanyAgencyName = Tools_System_Tools::DEFAUL_MOJO_COMPANY_AGENCY_NAME;

        if(!empty(Zend_Controller_Action_HelperBroker::getStaticHelper('config')->getConfig('mojoCompanyAgencyName'))) {
            $mojoCompanyAgencyName = $toasterConfig['mojoCompanyAgencyName'];
        }

        $this->_view->mojoCompanyAgencyName = $mojoCompanyAgencyName;

        return $this->_view->render('admin/adminpanel.phtml');
	}



}

