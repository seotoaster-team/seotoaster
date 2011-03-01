<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Page
 *
 * @author iamne
 */
class Helpers_Action_Page extends Zend_Controller_Action_Helper_Abstract {

	public function validateRequestedPage($pageUrl) {
		if(!$pageUrl) {
			$websiteConfig = Zend_Registry::get('website');
			$pageUrl = $websiteConfig['defaultPage'];
		}
		elseif(!preg_match('/\.html$/', $pageUrl)) {
			$pageUrl .= '.html';
		}
		return $pageUrl;
	}

	public function show404page() {
		$acl = Zend_Registry::get('acl');
	}

}

