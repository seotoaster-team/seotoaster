<?php

/**
 * Page garbage collector
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Tools_Page_GarbageCollector extends Tools_System_GarbageCollector {

	protected function _runOnDefault() {

	}

	protected function _runOnCreate() {
		$this->_cleanCachedPageData();
	}

	protected function _runOnUpdate() {
		$this->_cleanDraftCache();
		$this->_cleanOptimized();
		$this->_cleanCachedPageData();
		$this->_resetSearchIndexRenewFlag();
	}


	protected function _runOnDelete() {
		$this->_removePageUrlFromContent();
		Tools_Filesystem_Tools::saveFile('sitemap.xml', Tools_Content_Feed::generateSitemapFeed());
		Tools_Search_Tools::removeFromIndex($this->_object->getId());
		$this->_cleanCachedPageData();
		$this->_resetSearchIndexRenewFlag();
	}

	/**
	 * @todo improve/ optimize?
	 */
	private function _removePageUrlFromContent() {
		$websiteHelper       = Zend_Controller_Action_HelperBroker::getStaticHelper('Website');
		$websiteUrl          = $websiteHelper->getUrl();
		unset ($websiteHelper);
		$data                = Application_Model_Mappers_LinkContainerMapper::getInstance()->findByLink($websiteUrl . $this->_object->getUrl());
		if(is_array($data) && !empty ($data)) {
			$containerMapper = Application_Model_Mappers_ContainerMapper::getInstance();
			foreach ($data as $containerData) {
				$container = $containerMapper->find($containerData['id_container']);

				$container->registerObserver(new Tools_Content_GarbageCollector(array(
					'action' => Tools_System_GarbageCollector::CLEAN_ONUPDATE
				)));

				if(!$container instanceof Application_Model_Models_Container) {
					continue;
				}
				$urlPattern = '~<a\s+.*\s*href="' . $containerData['link'] . '"\s*.*\s*>.*</a>~uUs';

				$content = preg_replace($urlPattern, '', $container->getContent());
				$container->setContent($content);
				$containerMapper->save($container);
				$container->notifyObservers();
			}
		}
	}

	private function _removeRelatedContainers() {
		$containerMapper = Application_Model_Mappers_ContainerMapper::getInstance();
		$containers      = $containerMapper->findByPageId($this->_object->getId());
		if(!empty ($containers)) {
			foreach ($containers as $container) {
				$containerMapper->delete($container);
			}
		}
	}

	private function _cleanDraftCache() {
		// Cleaning draft cache if draft state of the page was changed
		$cacheHelper   = Zend_Controller_Action_HelperBroker::getStaticHelper('cache');
		$sessionHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('session');
		if($this->_object->getDraft() != $sessionHelper->oldPageDraft) {
			$cacheHelper->clean(Helpers_Action_Cache::KEY_DRAFT, Helpers_Action_Cache::PREFIX_DRAFT);
		}
		unset($cacheHelper);
		unset($sessionHelper);
	}

	private function _cleanOptimized() {
		$optimizedDbTable = new Application_Model_DbTable_Optimized();
		$optimizedExists  = $optimizedDbTable->find($this->_object->getId())->current();
		if($optimizedExists && !$this->_object->getOptimized()) {
			$optimizedDbTable->delete(array('page_id = ?' => $this->_object->getId()));
		}
	}

	private function _resetSearchIndexRenewFlag() {
		$cacheHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('cache');
		$cacheHelper->clean(null, null, array('search_index_renew'));
	}

	private function _cleanCachedPageData(){
		$cacheHelper   = Zend_Controller_Action_HelperBroker::getStaticHelper('cache');
		$cacheHelper->clean($this->_object->getUrl(), 'pagedata_');
		$tags = array(
			'pageid_'. $this->_object->getId(),
			'Widgets_Menu_Menu',
			'Widgets_Related_Related',
            'pageTags',
            'Widgets_List_List',
            'sitemaps'
		);
		$cacheHelper->clean(false, false, $tags);
	}
}

