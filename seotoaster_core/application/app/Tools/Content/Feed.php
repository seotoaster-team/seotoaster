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

    const SMFEED_TYPE_REGULAR          = '';
    const SMFEED_TYPE_INDEX            = 'index';

	/**
	 * Generates sitemap xml feed. According to the sitemap protocol described at sitemaps.org
	 *
	 * @see http://www.sitemaps.org/protocol.php
     * @deprecated
	 * @return xml
	 */
	public static function generateSitemapFeed() {}

}

