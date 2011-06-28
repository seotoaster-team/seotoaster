<?php

/**
 * Seo watchdog
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Tools_Seo_Watchdog implements Interfaces_Observer {

	private $_object = null;

	public function notify($object) {
		$this->_object = $object;
		if($this->_object instanceof Application_Model_Models_Container) {
			$this->_contentUpdateChain();
		}
		if($this->_object instanceof Application_Model_Models_Page) {
			$this->_pageUpdateChain();
		}
	}

	public function updateSitemap() {
		return $this->_updateSitemap();
	}

	public function updateRobotsTxt() {
		return $this->_updateRobotsTxt();
	}

	public function updateLinkDependencies() {
		return $this->_updateLinkDependencies();
	}


	private function _pageUpdateChain() {
		$this->_updateContainersUrls();
		$this->_update301Redirects();
		$this->_updateSitemap();
		//@todo update deeplinks
	}

	private function _contentUpdateChain() {
		$this->_updateLinksTitles();
	}

	private function _updateSitemap() {
		$sitemapFeed = Tools_Content_Feed::generateSitemapFeed();
		return Tools_Filesystem_Tools::saveFile('sitemap.xml', $sitemapFeed);
	}

	private function _updateContainersUrls() {

		if(!$this->_object instanceof Application_Model_Models_Page) {
			throw new Exceptions_SeotoasterException('Wrong object given. Instance of Application_Model_Models_Page expected.');
		}

		$sessionHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('Session');
		$websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('Website');

		if(!isset($sessionHelper->oldPageUrl) || !$sessionHelper->oldPageUrl) {
			return null;
		}

		$fullOldUrl         = $websiteHelper->getUrl() . $sessionHelper->oldPageUrl;
		$mapper             = new Application_Model_Mappers_LinkContainerMapper();
		$containersToUpdate = $mapper->findByLink($fullOldUrl);
		unset($mapper);

		if(!empty ($containersToUpdate)) {
			$mapper = new Application_Model_Mappers_ContainerMapper();
			foreach ($containersToUpdate as $containerData) {
				$container        = $mapper->find($containerData['id_container']);
				$links            = Tools_Content_Tools::findLinksInContent($container->getContent(), true);
				$container->registerObserver(new Tools_Content_GarbageCollector());
				if(in_array($fullOldUrl, $links)) {
					$fullNewUrl             = $websiteHelper->getUrl() . $this->_object->getUrl();
					$withoutTitleUrlPattern = '~(<a\s+[^\s]*\s*href=")(' . $fullOldUrl . ')("\s*)(>.+</a>)~u';

					$container->setContent(preg_replace($withoutTitleUrlPattern, '$1' . $fullNewUrl . '$3 title="' . $this->_object->getH1() . '" $4', $container->getContent()));
					$container->setContent(str_replace('title="' . $sessionHelper->oldPageH1 . '"', 'title="' . $this->_object->getH1() . '"', $container->getContent()));
					$container->setContent(str_replace($fullOldUrl, $fullNewUrl, $container->getContent()));

					$mapper->save($container);
					$container->notifyObservers();
				}
			}
		}
		unset($sessionHelper->oldPageH1);
	}

	private function _update301Redirects() {
		$mapper        = new Application_Model_Mappers_RedirectMapper();
		$sessionHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('Session');
		$cacheHelper   = Zend_Controller_Action_HelperBroker::getStaticHelper('Cache');

		if(!isset($sessionHelper->oldPageUrl) || !$sessionHelper->oldPageUrl) {
			return null;
		}

		if($sessionHelper->oldPageUrl == $this->_object->getUrl()) {
			return null;
		}

		$mapper->deleteByRedirect($this->_object->getUrl(), $sessionHelper->oldPageUrl);

		$redirect = new Application_Model_Models_Redirect();
		$redirect->setFromUrl($sessionHelper->oldPageUrl);
		$redirect->setToUrl($this->_object->getUrl());
		$redirect->setPageId($this->_object->getId());
		$mapper->save($redirect);

		$cacheHelper->clean('toaster_301redirects', '301redirects');
	}


	private function _updateLinksTitles() {
		if($this->_object instanceof Application_Model_Models_Container) {
			$websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('Website');
			$links         = array_unique(Tools_Content_Tools::findLinksInContent($this->_object->getContent(), true, Tools_Content_Tools::PATTERN_LINKWITHOUTTITLE));
			if(!empty($links)) {
				$pageMapper      = new Application_Model_Mappers_PageMapper();
				$containerMapper = new Application_Model_Mappers_ContainerMapper();
				foreach ($links as $link) {
					$page = $pageMapper->findByUrl(str_replace($websiteHelper->getUrl(), '', $link));

					if($page === null) {
						continue;
					}

					$h1   = $page->getH1();
					unset($page);

					$withoutTitleUrlPattern = '~(<a\s+[^\s]*\s*href="' . $link . ')("\s*)(>.+</a>)~u';
					$this->_object->setContent(preg_replace($withoutTitleUrlPattern, '$1$2 title="' . $h1 . '" $3', $this->_object->getContent()));
					$containerMapper->save($this->_object);
				}
			}
		}
	}

}

