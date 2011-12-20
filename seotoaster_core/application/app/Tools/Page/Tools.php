<?php

/**
 * Tools
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Tools_Page_Tools {

	public static function getPreviewPath($pageId, $capIfNoPreview = false, $croped = false) {
		$websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
		$pageHelper    = Zend_Controller_Action_HelperBroker::getStaticHelper('page');
		$previews      = Tools_Filesystem_Tools::findFilesByExtension($websiteHelper->getPath() . (($croped) ? $websiteHelper->getPreviewcrop() :$websiteHelper->getPreview()), 'jpg|png|jpeg|gif', true, true, false);

		$page          = Application_Model_Mappers_PageMapper::getInstance()->find($pageId);
		if($page instanceof Application_Model_Models_Page) {
			$cleanUrl = $pageHelper->clean($page->getUrl());
			unset($page);
			$path = (array_key_exists($cleanUrl, $previews)) ? str_replace($websiteHelper->getPath(), $websiteHelper->getUrl(), $previews[$cleanUrl]) : '';
			if(!$path && $capIfNoPreview) {
				return $websiteHelper->getUrl() . 'system/images/noimage.png';
			}
			return $path;
		}
		$websiteHelper->getUrl() . 'system/images/noimage.png';
	}

	public static function getDraftPages() {
		$cacheHelper    = Zend_Controller_Action_HelperBroker::getStaticHelper('cache');
		if(null === ($draftPages = $cacheHelper->load(Helpers_Action_Cache::KEY_DRAFT, Helpers_Action_Cache::PREFIX_DRAFT))) {
			$draftPages = Application_Model_Mappers_PageMapper::getInstance()->fetchAllDraftPages();
			$cacheHelper->save(Helpers_Action_Cache::KEY_DRAFT, $draftPages, Helpers_Action_Cache::PREFIX_DRAFT, array(), Helpers_Action_Cache::CACHE_LONG);
		}
		return $draftPages;
	}

	public static function getLandingPage($type) {
		if(!isset($type) || empty ($type)) {
			throw new Exceptions_SeotoasterException('You should specify landing page type');
		}
		$landingPage = null;
		switch ($type) {
			case Application_Model_Models_Page::OPT_SIGNUPLAND:
				$landingPage = Application_Model_Mappers_PageMapper::getInstance()->findSignupLandign();
			break;
			case Application_Model_Models_Page::OPT_MEMLAND:
				$landingPage = Application_Model_Mappers_PageMapper::getInstance()->findMemberLanding();
			break;
			case Application_Model_Models_Page::OPT_ERRLAND:
				$landingPage = Application_Model_Mappers_PageMapper::getInstance()->findErrorLoginLanding();
			break;
		}
		return $landingPage;
	}

	public static function getCheckoutPage() {
		return Application_Model_Mappers_PageMapper::getInstance()->findCheckout();
	}
}

