<?php

/**
 * Feed tool. Generates content for all possible feeds. Such as sitempa.xml, full.xml, all.xml, etc...
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Tools_Content_Feed {

	/**
	 * Sitemap feed related constants
	 *
	 */
	const SMFEED_HIGHPRIORITY_PAGE_URL = 'index.html';
	const SMFEED_CHANGEFREEQ           = 'daily';

	/**
	 * Generates sitemap xml feed. According to the sitemap protocol described at sitemaps.org
	 *
	 * @see http://www.sitemaps.org/protocol.php
	 * @return xml
	 */
	public static function generateSitemapFeed() {

		$websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');

		$sitemapFeed  = '<?xml version="1.0" encoding="UTF-8" ?>' . PHP_EOL;
		$sitemapFeed .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

		$pages       = Application_Model_Mappers_PageMapper::getInstance()->fetchAll();

		if(empty ($pages)) {
			return '';
		}
		foreach ($pages as $page) {
			if($page->getParentId() == Application_Model_Models_Page::IDCATEGORY_DRAFT) {
				continue;
			}
			$priority     = ($page->getUrl() == self::SMFEED_HIGHPRIORITY_PAGE_URL) ? '1' : '0.8';
			//$sitemapFeed .= '<url>' . PHP_EOL . '<loc>' . urlencode($websiteHelper->getUrl() . (($page->getUrl() == 'index.html') ? '' : $page->getUrl())) . '</loc>' . PHP_EOL;
			$sitemapFeed .= '<url>' . PHP_EOL . '<loc>' . $websiteHelper->getUrl() . (($page->getUrl() == 'index.html') ? '' : $page->getUrl()) . '</loc>' . PHP_EOL;
			$sitemapFeed .= '<lastmod>' . date('c', time()) . '</lastmod>' . PHP_EOL;
			$sitemapFeed .= '<changefreq>' . self::SMFEED_CHANGEFREEQ . '</changefreq>' . PHP_EOL;
			$sitemapFeed .= '<priority>' . $priority . '</priority>' . PHP_EOL . '</url>' . PHP_EOL;
		}

		$sitemapFeed .= '</urlset>';
		return $sitemapFeed;
	}

}

