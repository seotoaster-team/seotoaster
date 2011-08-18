<?php

/**
 * Tools
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Tools_Page_Tools {

	public static function getPreviewPath($pageId, $capIfNoPreview = false) {
		$websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
		$pageHelper    = Zend_Controller_Action_HelperBroker::getStaticHelper('page');
		$previews      = Tools_Filesystem_Tools::findFilesByExtension($websiteHelper->getPath() . $websiteHelper->getPreview(), 'jpg|png|jpeg|gif', true, true);

		$mapper        = new Application_Model_Mappers_PageMapper();
		$page          = $mapper->find($pageId);
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

}

