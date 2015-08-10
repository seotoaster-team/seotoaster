<?php

/**
 * Tools
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Tools_Search_Tools
{

    /**
     * @var Zend_Search_Lucene_Interface
     */
    private static $_index;

    /**
     * Initialize search index to static property
     * @return Zend_Search_Lucene_Interface
     */
    public static function initIndex()
    {
        if (self::$_index instanceof Zend_Search_Lucene_Interface) {
            return self::$_index;
        }
        Zend_Search_Lucene_Analysis_Analyzer::setDefault(
            new Zend_Search_Lucene_Analysis_Analyzer_Common_Utf8Num_CaseInsensitive ()
        );
        $searchIndexPath = Zend_Controller_Action_HelperBroker::getStaticHelper('website')->getPath(
            ) . 'cache/' . Widgets_Search_Search::INDEX_FOLDER;

        if (!is_dir($searchIndexPath)) {
            if (!Tools_Filesystem_Tools::mkDir($searchIndexPath)) {
                Tools_System_Tools::debugMode() && error_log(
                    'Can\'t create search index folder in ' . $searchIndexPath
                );
            }
        }

        try {
            self::$_index = Zend_Search_Lucene::open($searchIndexPath);
        } catch (Exception $e) {
            self::$_index = Zend_Search_Lucene::create($searchIndexPath);
        }

        return self::$_index;
    }

    public static function renewIndex($forceCreate = false)
    {
        $pages = Application_Model_Mappers_PageMapper::getInstance()->getPagesForSearchIndex();
        if (!is_array($pages)) {
            return false;
        }

        Zend_Search_Lucene_Search_QueryParser::setDefaultEncoding('UTF-8');
        Zend_Search_Lucene_Analysis_Analyzer::setDefault(
            new Zend_Search_Lucene_Analysis_Analyzer_Common_Utf8Num_CaseInsensitive ()
        );

        self::removeIndex() && self::initIndex();

        array_walk($pages, array('Tools_Search_Tools', 'addPageToIndex'));

        self::$_index->optimize();
    }

    public static function removeFromIndex($term)
    {
        $websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
        $searchIndexFolder = $websiteHelper->getPath() . 'cache/' . Widgets_Search_Search::INDEX_FOLDER;
        if (!is_dir($searchIndexFolder)) {
            return false;
        }

        if (!self::initIndex()) {
            return false;
        }

        $hits = self::$_index->find(strval($term));
        if (is_array($hits) && !empty ($hits)) {
            foreach ($hits as $hit) {
                self::$_index->delete($hit->id);
            }
            return true;
        }
        return false;
    }


    public static function addPageToIndex($page, $toasterSearchIndex = false)
    {
        if (!self::initIndex()) {
            return false;
        }

        if ($page instanceof Application_Model_Models_Page) {
            $page = $page->toArray();

            $containers = Application_Model_Mappers_ContainerMapper::getInstance()->findByPageId($page['id']);
            $page['content'] = '';

            if (!empty ($containers)) {
                foreach ($containers as $container) {
                    $page['content'] .= $container->getContent();
                }
            }

            if ($page['externalLinkStatus'] === '1') {
                $page['url'] = $page['externalLink'];
            }
        }

        $document = new Zend_Search_Lucene_Document();

        $document->addField(Zend_Search_Lucene_Field::keyword('pageId', $page['id']));
        $document->addField(Zend_Search_Lucene_Field::unStored('metaKeyWords', $page['metaKeywords'], 'UTF-8'));
        $document->addField(Zend_Search_Lucene_Field::unStored('metaDescription', $page['metaDescription'], 'UTF-8'));
        $document->addField(Zend_Search_Lucene_Field::unStored('headerTitle', $page['headerTitle'], 'UTF-8'));
        $document->addField(Zend_Search_Lucene_Field::unStored('content', $page['content'], 'UTF-8'));
        $document->addField(Zend_Search_Lucene_Field::unIndexed('modified', time(), 'UTF-8'));
        $document->addField(Zend_Search_Lucene_Field::unIndexed('draft', $page['draft'], 'UTF-8'));
        $document->addField(Zend_Search_Lucene_Field::unIndexed('url', $page['url'], 'UTF-8'));
        $document->addField(Zend_Search_Lucene_Field::text('teaserText', $page['teaserText'], 'UTF-8'));
        $document->addField(Zend_Search_Lucene_Field::text('navName', $page['navName'], 'UTF-8'));
        $document->addField(Zend_Search_Lucene_Field::text('h1', $page['h1'], 'UTF-8'));
        $document->addField(Zend_Search_Lucene_Field::keyword('pageType', $page['pageType']));

        self::$_index->addDocument($document);
    }


    public static function removeIndex()
    {
        $websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
        $searchIndexFolder = $websiteHelper->getPath() . 'cache/' . Widgets_Search_Search::INDEX_FOLDER;
        if (!is_dir($searchIndexFolder)) {
            return false;
        }
        Tools_Filesystem_Tools::deleteDir($searchIndexFolder);
    }

    public static function commit()
    {
        self::initIndex()->commit();
    }

    public static function optimize()
    {
        self::initIndex()->optimize();
    }

    public static function isEmpty()
    {
        return (bool)self::initIndex()->numDocs() ? false : true;
    }
}

