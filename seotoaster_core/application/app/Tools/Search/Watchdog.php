<?php

/**
 * Watchdog
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Tools_Search_Watchdog implements Interfaces_Observer {

	private $_options = array();

	private $_object  = null;

	public function __construct($options = array()) {
		$this->_options = $options;
	}

	public function notify($object) {
		$this->_object = $object;
		if($this->_object instanceof Application_Model_Models_Page) {
			$this->_onPageUpdateChain();
		}
		if($this->_object instanceof Application_Model_Models_Container) {
			$this->_onContainerUpdateChain();
		}
	}


	private function _onPageUpdateChain() {
		// add / update page in the search index
		Tools_Search_Tools::removeFromIndex($this->_object->getId());

        $pageObject = $this->_customSearchPages($this->_object);

        Tools_Search_Tools::addPageToIndex($pageObject);
	}

	private function _onContainerUpdateChain() {
		$page = Application_Model_Mappers_PageMapper::getInstance()->find($this->_object->getPageId());
		if($page !== null) {
			Tools_Search_Tools::removeFromIndex($page->getId());

            $page = $this->_customSearchPages($page);
			Tools_Search_Tools::addPageToIndex($page);
		}
	}

	private function _customSearchPages($pageObject)
    {

        $dbAdapter = Zend_Registry::get('dbAdapter');
        $isEcommerce = in_array('shopping_product', $dbAdapter->listTables());
        $newslogExists = in_array('plugin_newslog_news', $dbAdapter->listTables());

        if ($isEcommerce) {
            $where = $dbAdapter->quoteInto('p.page_id = ?', $pageObject->getId());
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
                ->where($where)
                ->group('p.id');

            $product = $dbAdapter->fetchAssoc($productSQL);

            if (array_key_exists($pageObject->getId(), $product)) {
                $prod = $product[$pageObject->getId()];
                $pageObject->setH1(implode(', ', array($prod['name'], $prod['sku'], $prod['mpn'], $pageObject->getH1())));

                $pageObject->setTeaserText(implode(
                    PHP_EOL,
                    array(
                        '<div class="search-product-short-description">'.$prod['short_description'].'</div>',
                        '<div class="search-product-full-description">'.$prod['full_description'].'</div>',
                        '<div class="search-teaser-text">'.$pageObject->getTeaserText().'</div>',
                        '<div class="search-product-tags">'.$prod['tags'].'</div>'
                    ))
                );
            }
        }

        if($newslogExists) {
            $where = $dbAdapter->quoteInto('pnn.page_id = ?', $pageObject->getId());
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
                ->where($where)
                ->group('pnn.page_id');
            $news = $dbAdapter->fetchAssoc($newslogSQL);

            if (array_key_exists($pageObject->getId(), $news)) {
                $singleNews = $news[$pageObject->getId()];
                $pageObject->setMetaDescription(implode(
                    ', ',
                    array($pageObject->getMetaDescription(), $singleNews['content'], $singleNews['tags'])
                ));

                $pageObject->setPageTags($singleNews['tags']);
            }
        }

        return $pageObject;
    }
}

