<?php

/**
 * Tools
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Tools_Seo_Tools {

	private static $_websiteHelper = null;

	public static function runPageRankSculpting($siloId, $pageContent) {
		self::$_websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
		$silo                 = Application_Model_Mappers_SiloMapper::getInstance()->find($siloId);
		$sculptingReplacement = array();
		$links                = Tools_Content_Tools::findLinksInContent($pageContent);
		if(empty ($links) || !isset($links[1])) {
			return $pageContent;
		}
		$hrefs           = array_combine($links[0], $links[1]);
		$siloedPagesUrls = array_merge(array(
			self::$_websiteHelper->getUrl() . 'index.html',
			self::$_websiteHelper->getUrl() . 'index.htm',
		), array_map(array('self', '_callbackUrls'), $silo->getRelatedPages()));
		foreach ($hrefs as $key => $href) {
			if(in_array($href, $siloedPagesUrls)) {
				unset($hrefs[$key]);
				continue;
			}
			$page = Application_Model_Mappers_PageMapper::getInstance()->findByUrl(str_replace(self::$_websiteHelper->getUrl(), '', $href));
			if($page === null) {
				continue;
			}
			$pageContent            = str_replace($key, '<span class="' . md5($key) . '">' . $page->getNavName() . '</span>', $pageContent);
			$sculptingReplacement[] = array(
				'id'   => md5($key),
				'repl' => $key
			);
			unset($page);
		}
		Zend_Registry::set('sculptingReplacement', json_encode($sculptingReplacement));
		return $pageContent;
	}

	private static function _callbackUrls($page) {
		return self::$_websiteHelper->getUrl() . $page->getUrl();
	}

	public static function loadSeodata() {
		$mapper  = Application_Model_Mappers_SeodataMapper::getInstance();
		$seoData = $mapper->fetchAll();
		if(is_array($seoData) && !empty($seoData)) {
			return $seoData[0];
		}
		return new Application_Model_Models_Seodata();
	}

}

