<?php

/**
 * Tools
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Tools_Page_Tools {

	public static function getPreviewPath($pageId, $capIfNoPreview = false, $croped = false) {
		$websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
		$configHelper  = Zend_Controller_Action_HelperBroker::getStaticHelper('config');
		$pageHelper    = Zend_Controller_Action_HelperBroker::getStaticHelper('page');
        $websiteUrl    = ($configHelper->getConfig('mediaServers') ? Tools_Content_Tools::applyMediaServers($websiteHelper->getUrl()) : $websiteHelper->getUrl());
		try {
            $previews      = Tools_Filesystem_Tools::findFilesByExtension($websiteHelper->getPath() . (($croped) ? $websiteHelper->getPreviewCrop() :$websiteHelper->getPreview()), 'jpg|png|jpeg|gif', true, true, false);
        } catch (Exceptions_SeotoasterException $se) {
            if(APPLICATION_ENV == 'development') {
                error_log("(Cant find preview thumbnail because: " . $se->getMessage() . "\n" . $se->getTraceAsString());
            }
            return $websiteUrl . 'system/images/noimage.png';
        }

		$page = Application_Model_Mappers_PageMapper::getInstance()->find($pageId);
		if($page instanceof Application_Model_Models_Page) {
			$cleanUrl = $pageHelper->clean(preg_replace('~/+~', '-', $page->getUrl()));
			unset($page);
			$path = (array_key_exists($cleanUrl, $previews)) ? str_replace($websiteHelper->getPath(), $websiteUrl, $previews[$cleanUrl]) : '';
			if(!$path && $capIfNoPreview) {
				return $websiteUrl . 'system/images/noimage.png';
			}
			return $path;
		}
		return $websiteUrl . 'system/images/noimage.png';
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

	public static function getProductCategoryPage() {
		// We need to know product category page url
		// This url specified in the bundle plugin "Shopping"
		// But this plugin may not be present in the system (not recommended)
		$shopping = Tools_Plugins_Tools::findPluginByName('shopping');
		$pageUrl  = ($shopping->getStatus() == Application_Model_Models_Plugin::ENABLED) ? Shopping::PRODUCT_CATEGORY_URL : null;
		if($pageUrl === null) {
			return null;
		}
		return Application_Model_Mappers_PageMapper::getInstance()->findByUrl($pageUrl);
	}

	public static function getPagesCountByTemplate($templateName) {
		$pageDbTable   = new Application_Model_DbTable_Page();
		return $pageDbTable->getAdapter()->query($pageDbTable->select()->where('template_id="' . $templateName . '"'))->rowCount();
	}
}

