<?php

class Api_Toaster_Searchreindex extends Api_Service_Abstract
{
    const INDEX_PAGES_LIMIT = 50;

    /**
     * @var Helpers_Action_Session
     */
    private $_sessionHelper;

    protected $_websiteHelper = null;

    protected $_translator = null;

    /**
     * @var array Access Control List
     */
    protected $_accessList = array(
        Tools_Security_Acl::ROLE_SUPERADMIN => array('allow' => array('post')),
    );

    public function init()
    {
        parent::init();
        $this->_sessionHelper = Zend_Controller_Action_HelperBroker::getExistingHelper('session');
        $this->_websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
        $this->_translator = Zend_Registry::get('Zend_Translate');
    }

    public function getAction()
    {
    }

    public function postAction()
    {
        $secureToken = $this->_request->getParam(Tools_System_Tools::CSRF_SECURE_TOKEN, false);
        $tokenValid = Tools_System_Tools::validateToken($secureToken, Tools_System_Tools::ACTION_PREFIX_CONFIG);
        if (!$tokenValid) {
            $this->_responseHelper->fail('');
        }
        $currentUserRole = $this->_sessionHelper->getCurrentUser()->getRoleId();

        if ($currentUserRole === Tools_Security_Acl::ROLE_SUPERADMIN) {
            $responseHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('response');
            $indexPagesOffset = !empty($this->_sessionHelper->indexPagesOffset) ? $this->_sessionHelper->indexPagesOffset : 0;
            $searchIndexFolder = $this->_websiteHelper->getPath() . 'cache/' . Widgets_Search_Search::INDEX_FOLDER;
            if (!$indexPagesOffset && is_dir($searchIndexFolder) && !Tools_Filesystem_Tools::deleteDir($searchIndexFolder)) {
                $responseHelper->fail($this->_translator->translate('Can\'t clean a search folder'));
            }
            $dbAdapter = Zend_Registry::get('dbAdapter');
            $select = $dbAdapter->select()
                ->from(array('p' => 'page'), null)
                ->joinLeft(array('o' => 'optimized'), 'p.id = o.page_id', null)
                ->joinLeft(array('c' => 'container'), 'p.id = c.page_id AND c.container_type = 1', null)
                ->columns(
                    array(
                        'id' => 'p.id',
                        'url' => new Zend_Db_Expr('COALESCE(o.url, p.url)'),
                        'h1' => new Zend_Db_Expr('COALESCE(o.h1, p.h1)'),
                        'navName' => new Zend_Db_Expr('COALESCE(o.nav_name, p.nav_name)'),
                        'headerTitle' => new Zend_Db_Expr('COALESCE(o.header_title, p.header_title)'),
                        'metaKeywords' => new Zend_Db_Expr('COALESCE(o.meta_keywords, p.meta_keywords)'),
                        'metaDescription' => new Zend_Db_Expr('COALESCE(o.meta_description, p.meta_description)'),
                        'teaserText' => new Zend_Db_Expr('COALESCE(o.teaser_text, p.teaser_text)'),
                        'content' => new Zend_Db_Expr('GROUP_CONCAT(c.content)'),
                        'draft' => 'p.draft',
                        'pageType' => 'p.page_type'
                    )
                )
                ->where("p.system = '?'", 0)
                ->where("p.draft = '?'", 0)
                ->where("p.parent_id <> '?'", -5)
                ->group('p.id');

            if(empty($this->_sessionHelper->indexPagesTotal)){
                $this->_sessionHelper->indexPagesTotal = count($dbAdapter->fetchAll($select));
            }

            $select->limit(self::INDEX_PAGES_LIMIT, $indexPagesOffset);
            $pages = $dbAdapter->fetchAll($select);
            if (is_array($pages) && !empty($pages)) {
                if (!is_dir($searchIndexFolder) && !Tools_Filesystem_Tools::mkDir($searchIndexFolder)) {
                    die('Can\'t create search index folder in ' . $searchIndexFolder);
                }
                $index = Tools_Search_Tools::initIndex();
                $index->setMergeFactor(100);

                //check if there is a product table exists
                $isEcommerce = in_array('shopping_product', $dbAdapter->listTables());
                if ($isEcommerce) {
                    $productSQL = $dbAdapter->select()->from(
                        array('p' => 'shopping_product'),
                        array(
                            'p.page_id',
                            'p.name',
                            'p.sku',
                            'p.mpn',
                            'p.short_description',
                            'p.full_description'
                        )
                    )->join(array('b' => 'shopping_brands'), 'b.id = p.brand_id', array('brand' => 'b.name'))
                        ->joinLeft(array('pht' => 'shopping_product_has_tag'), 'pht.product_id = p.id', null)
                        ->joinLeft(
                            array('t' => 'shopping_tags'),
                            't.id = pht.tag_id',
                            array('tags' => new Zend_Db_Expr('GROUP_CONCAT(t.name SEPARATOR ", ")'))
                        )
                        ->group('p.id');
                    $products = $dbAdapter->fetchAssoc($productSQL);
                }

                //check if there is a plugin_newslog_news table exists
                $news = array();
                $newslogExists = in_array('plugin_newslog_news', $dbAdapter->listTables());
                if ($newslogExists) {
                    $newslogSQL = $dbAdapter->select()->from(
                        array('pnn' => 'plugin_newslog_news'),
                        array(
                            'pnn.page_id',
                            'pnn.content',
                        )
                    )->join(array('p' => 'page'), 'p.id = pnn.page_id', array(''))
                        ->joinLeft(array('pnnht' => 'plugin_newslog_news_has_tag'), 'pnnht.news_id = pnn.id',
                            array())
                        ->joinLeft(
                            array('pnt' => 'plugin_newslog_tag'),
                            'pnt.id = pnnht.tag_id',
                            array('tags' => new Zend_Db_Expr('GROUP_CONCAT(pnt.name SEPARATOR ", ")'))
                        )
                        ->group('pnn.page_id');
                    $news = $dbAdapter->fetchAssoc($newslogSQL);
                }

                foreach ($pages as $i => $page) {
                    $this->_sessionHelper->currentPageId = $page['id'];
                    // if its ecommerce and this page is a product page
                    if ($isEcommerce && array_key_exists($page['id'], $products)) {
                        $prod = $products[$page['id']];
                        $page['h1'] = implode(
                            ', ',
                            array($prod['name'], $prod['sku'], $prod['mpn'], $page['h1'])
                        );
                        $page['teaserText'] = implode(
                            PHP_EOL,
                            array(
                                '<div class="search-product-short-description">'.$prod['short_description'].'</div>',
                                '<div class="search-product-full-description">'.$prod['full_description'].'</div>',
                                '<div class="search-teaser-text">'.$page['teaserText'].'</div>',
                                '<div class="search-product-tags">'.$prod['tags'].'</div>'
                            )
                        );
                    }
                    if ($newslogExists && array_key_exists($page['id'], $news)) {
                        $singleNews = $news[$page['id']];
                        $page['metaDescription'] = implode(
                            ', ',
                            array($page['metaDescription'], $singleNews['content'], $singleNews['tags'])
                        );

                        $page['pageTags'] = $singleNews['tags'];
                    }
                    Tools_Search_Tools::addPageToIndex($page);
                }
                Tools_Search_Tools::optimize();
                Tools_Search_Tools::commit();
            }
            $this->_sessionHelper->indexPagesOffset += count($pages);
            if (count($pages) < self::INDEX_PAGES_LIMIT) {
                $reindexedPagesTotal = $this->_sessionHelper->indexPagesOffset;
                $pagesTotal = $this->_sessionHelper->indexPagesTotal;
                unset($this->_sessionHelper->indexPagesOffset);
                unset($this->_sessionHelper->indexPagesTotal);
                $responseHelper->success(array(
                    'indexedPages' => $reindexedPagesTotal,
                    'pagesTotal' => $pagesTotal,
                    'final' => true
                ));
            }
            $responseHelper->success(array(
                'indexedPages' => $this->_sessionHelper->indexPagesOffset,
                'pagesTotal' => $this->_sessionHelper->indexPagesTotal
            ));
        }
    }

    public function putAction()
    {
    }

    public function deleteAction()
    {
    }
}
