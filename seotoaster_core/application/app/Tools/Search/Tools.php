<?php

/**
 * Tools
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Tools_Search_Tools {

	public static function renewIndex() {
		$websiteHelper     = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
		$searchIndexFolder = $websiteHelper->getPath() . 'cache/' . Widgets_Search_Search::INDEX_FOLDER;
		$pages             = Application_Model_Mappers_PageMapper::getInstance()->fetchAll();

		if(!is_array($pages)) {
			return false;
		}

		Zend_Search_Lucene_Search_QueryParser::setDefaultEncoding('UTF-8');
		Zend_Search_Lucene_Analysis_Analyzer::setDefault(
			new Zend_Search_Lucene_Analysis_Analyzer_Common_Utf8_CaseInsensitive ()
		);

		self::removeIndex();

		$toasterSearchIndex = (!is_dir($searchIndexFolder)) ? Zend_Search_Lucene::create($searchIndexFolder) : Zend_Search_Lucene::open($searchIndexFolder);

		foreach ($pages as $page) {
			self::addPageToIndex($page, $toasterSearchIndex);
		}
		//$toasterSearchIndex->optimize();
	}

	public static function removeFromIndex($term) {
		$websiteHelper     = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
		$searchIndexFolder = $websiteHelper->getPath() . 'cache/' . Widgets_Search_Search::INDEX_FOLDER;
		if(!is_dir($searchIndexFolder)) {
			return false;
		}
        try {
		    $toasterSearchIndex = Zend_Search_Lucene::open($searchIndexFolder);
        } catch (Exception $e) {
            if(APPLICATION_ENV == 'development') {
                error_log("(plugin: " . strtolower(get_called_class()) . ") " . $e->getMessage() . "\n" . $e->getTraceAsString());
            }
            return false;
        }
		$hits = $toasterSearchIndex->find(strval($term));
		if(is_array($hits) && !empty ($hits)) {
			foreach ($hits as $hit) {
				$toasterSearchIndex->delete($hit->id);
			}
			return true;
		}
		return false;
	}

	public static function addPageToIndex(Application_Model_Models_Page $page, $toasterSearchIndex = false) {
		if(!$toasterSearchIndex) {
			$websiteHelper     = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
			$searchIndexFolder = $websiteHelper->getPath() . 'cache/' . Widgets_Search_Search::INDEX_FOLDER;
			if(!is_dir($searchIndexFolder)) {
				return false;
			}
            try {
                $toasterSearchIndex = Zend_Search_Lucene::open($searchIndexFolder);
            } catch (Exception $e) {
                if(APPLICATION_ENV == 'development') {
                    error_log("(plugin: " . strtolower(get_called_class()) . ") " . $e->getMessage() . "\n" . $e->getTraceAsString());
                }
                return false;
            }
		}


		$contents   = '';
		$containers = Application_Model_Mappers_ContainerMapper::getInstance()->findByPageId($page->getId());

		if(!empty ($containers)) {
			foreach ($containers as $container) {
				$contents .= $container->getContent();
			}
		}
        //@todo save contents to the cache and do not query for containers each time

		$document = new Zend_Search_Lucene_Document();
		$document->addField(Zend_Search_Lucene_Field::keyword('pageId', $page->getId()));

		$document->addField(Zend_Search_Lucene_Field::unStored('metaKeyWords', $page->getMetaKeywords(), 'UTF-8'));
		$document->addField(Zend_Search_Lucene_Field::unStored('metaDescription', $page->getMetaDescription(), 'UTF-8'));
		$document->addField(Zend_Search_Lucene_Field::unStored('title', $page->getHeaderTitle(), 'UTF-8'));
		$document->addField(Zend_Search_Lucene_Field::unStored('contents', $contents, 'UTF-8'));

		$document->addField(Zend_Search_Lucene_Field::text('pageTeaser', $page->getTeaserText(), 'UTF-8'));
		$document->addField(Zend_Search_Lucene_Field::text('url', $page->getUrl(), 'UTF-8'));
		$document->addField(Zend_Search_Lucene_Field::text('navName', $page->getNavName(), 'UTF-8'));
		$document->addField(Zend_Search_Lucene_Field::text('h1', $page->getH1(), 'UTF-8'));

		$document->addField(Zend_Search_Lucene_Field::binary('pageImage', base64_encode(Tools_Page_Tools::getPreviewPath($page->getId()))));
		$toasterSearchIndex->addDocument($document);
	}


	public static function removeIndex() {
		$websiteHelper     = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
		$searchIndexFolder = $websiteHelper->getPath() . 'cache/' . Widgets_Search_Search::INDEX_FOLDER;
		if(!is_dir($searchIndexFolder)) {
			return false;
		}
		Tools_Filesystem_Tools::deleteDir($searchIndexFolder);
	}

}

