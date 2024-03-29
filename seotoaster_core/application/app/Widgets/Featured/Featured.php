<?php

/**
 * Featured widget. Takes care about featured:area, featured:page, etc...
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Widgets_Featured_Featured extends Widgets_Abstract
{
    const AREA_DESC_LENGTH   = '250';

    const AREA_PAGES_COUNT   = '5';

    const FEATURED_TYPE_PAGE = 'page';

    const FEATURED_TYPE_AREA = 'area';

    const FEATURED_TYPE_FILTERABLE = 'filterable';

    const FEATURED_FILTER_BY_ID = 'id';

    const FEATURED_FILTER_BY_HEADER_TITLE = 'header_title';

    const FEATURED_FILTER_BY_LAST_UPDATE = 'last_update';

    const FEATURED_FILTER_BY_H1 = 'h1';

    /**
     * Featuredarea template type
     */
    const TEMPLATE_FA_TYPE = 'type_fa_template';

    private $_configHelper = null;

    private $_filterable = false;

    private $_order = false;

    private $_orderType = false;

    private $_acceptedOrderTypes = array('ASC', 'DESC');

    private $_acceptedPageOrderFields = array('nav_name', 'header_title', 'url', 'last_update');

    protected function _init()
    {
        parent::_init();
        $this->_view             = new Zend_View(array('scriptPath' => dirname(__FILE__).'/views'));
        $this->_view->websiteUrl = Zend_Controller_Action_HelperBroker::getStaticHelper('website')->getUrl();
        $this->_configHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('config');
        $this->useImage          = false;
        $this->cropParams        = array();
        $this->cropSizeSubfolder = '';

        $withoutCacheOption = array_search('without_cache', $this->_options);
        if ($withoutCacheOption !== false) {
            $this->_cacheable = false;
            unset($this->_options[$withoutCacheOption]);
        }

        // checking if its area and random
        if (!empty($this->_options)
            && (reset($this->_options) === self::FEATURED_TYPE_AREA)
            && (1 === intval(end($this->_options)))
        ) {
            $this->_cacheable = false;
        }

        $filterable = array_search(self::FEATURED_TYPE_FILTERABLE, $this->_options);
        if ($filterable !== false) {
            unset($this->_options[$filterable]);
            $this->_filterable = true;
            $this->_cacheable = false;
        }
    }

    protected function _load()
    {
        $featuredType = array_shift($this->_options);
        $rendererName = '_renderFeatured' . ucfirst($featuredType);

        if ($featuredType == self::FEATURED_TYPE_AREA && isset($this->_options[3])) {
            $cropOption = $this->_options[3];
        }
        elseif ($featuredType == self::FEATURED_TYPE_PAGE && isset($this->_options[2])) {
            $cropOption = $this->_options[2];
        }

        // Image output options
        if (isset($cropOption) && ($cropOption == 'img' || $cropOption == 'imgc')) {
            $this->useImage = $cropOption;
        }
        elseif (isset($cropOption) && strpos($cropOption, 'imgc-') !== false) {
            preg_match('/^imgc-([0-9]+)x?([0-9]*)/i', $cropOption, $this->cropParams);
            if (isset($this->cropParams[1], $this->cropParams[2])
                && is_numeric($this->cropParams[1])
                && $this->cropParams[2] == ''
            ) {
                $this->cropParams[2] = $this->cropParams[1];
            }
            unset($this->cropParams[0]);
            $this->useImage = 'imgc';
        }

        if (!empty($this->cropParams)) {
            $this->cropSizeSubfolder = implode('-', $this->cropParams).DIRECTORY_SEPARATOR;
        }

        // Create a folder crop-size subfolder
        if ($this->useImage == 'imgc' && $this->cropSizeSubfolder != '') {
            $websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
            $pathPreview   = $websiteHelper->getPath().$websiteHelper->getPreviewCrop().$this->cropSizeSubfolder;
            if (!is_dir($pathPreview)) {
                Tools_Filesystem_Tools::mkDir($pathPreview);
            }
        }
        $width = '';
        $height = '';
        if(!empty($this->cropParams)) {
            $width = $this->cropParams[1];
            $height = $this->cropParams[2];
        }

        $this->_view->width             = $width;
        $this->_view->height            = $height;
        $this->_view->useImage          = $this->useImage;
        $this->_view->cropParams        = $this->cropParams;
        $this->_view->cropSizeSubfolder = $this->cropSizeSubfolder;

        // Set template
        $template = current(preg_grep('/template=*/', $this->_options));
        if ($template) {
            $template = Application_Model_Mappers_TemplateMapper::getInstance()->find(
                preg_replace('/template=/', '', $template)
            );

            if ($template instanceof Application_Model_Models_Template) {
                $this->_view->tmplFaContent = $template->getContent();
            }
        }
        unset($template);

        $pageTitleWrap = current(preg_grep('/pageTitleWrap=*/', $this->_options));
        if (!empty($pageTitleWrap)) {
            $pageTitleWrap = preg_replace('/pageTitleWrap=/', '', $pageTitleWrap);
            $pageTitleWrapData = explode('.', $pageTitleWrap);
            $pageTitleWrapEl = array_shift($pageTitleWrapData);
            $pageTitleWrapClasses = implode(' ', $pageTitleWrapData);
            $this->_view->pageTitleWrapEl = $pageTitleWrapEl;
            $this->_view->pageTitleWrapClasses = $pageTitleWrapClasses;
            $this->_view->pageTitleWrap = $pageTitleWrap;
        }


        $this->_order = current(preg_grep('/order=*/', $this->_options));
        $this->_orderType = current(preg_grep('/orderType=*/', $this->_options));
        if (method_exists($this, $rendererName)) {
            return $this->$rendererName($this->_options);
        }
        throw new Exceptions_SeotoasterWidgetException($this->_translator->translate('Wrong featured type'));
    }

    private function _renderFeaturedArea($params)
    {
        if (!is_array($params)
            || empty($params)
            || !isset($params[0])
            || !$params[0]
            || preg_match('~^\s*$~', $params[0])
        ) {
            throw new Exceptions_SeotoasterWidgetException(
                $this->_translator->translate('Featured area name required.')
            );
        }

        $limit = (isset($params[1]) && $params[1]) ? $params[1] : self::AREA_PAGES_COUNT;
        if ($this->_filterable) {
            return $this->_filterFa($limit, $params[0]);
        }

        $customOrder = false;
        $customOrderType = 'ASC';
        if (!empty($this->_order)) {
            $setOrder = preg_replace('/order=/', '', $this->_order);
            if (in_array($setOrder, $this->_acceptedPageOrderFields)) {
                $customOrder = $setOrder;
            }
        }
        if (!empty($this->_orderType)) {
            $setCustomType = preg_replace('/orderType=/', '', $this->_orderType);
            if (in_array($setCustomType, $this->_acceptedOrderTypes)) {
                $customOrderType = $setCustomType;
            }
        }

        $featuredArea = Application_Model_Mappers_FeaturedareaMapper::getInstance()->findByName($params[0], true, $customOrder, $customOrderType);
        if ($featuredArea === null) {
            if (!Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_CONTENT)) {
                return '';
            }

            return $this->_translator->translate('Featured area ').$params[0].$this->_translator->translate(
                ' does not exist'
            );
        }

        // Set limit and on/off random
        $featuredArea->setLimit($limit)
            ->setRandom((intval(end($params)) === 1) ? true : false);

        $this->_view->faPages   = $featuredArea->getPages();
        $this->_view->faId      = $featuredArea->getId();
        $this->_view->faName    = $featuredArea->getName();
        $class                  = current(preg_grep('/class=*/', $params));
        $class = ($class !== null) ? preg_replace('/class=/', '', $class) : '';

        $template = current(preg_grep('/template=*/', $params));
        $templateClass = ($template !== null) ? preg_replace('/template=/', '', str_replace(' ', '', $template)) : '';

        $classesArray = array();

        if(!empty($class) && !in_array($class, $classesArray)) {
            $classesArray[] = $class;
        }

        if(!empty($templateClass) && !in_array($templateClass, $classesArray)) {
            $classesArray[] = $templateClass;
        }

        $classes = '';
        if(!empty($classesArray)) {
            $classes = implode(' ', $classesArray);
        }

        $this->_view->listClass = $classes;
        $this->_view->faPageDescriptionLength = (isset($params[2]) && is_numeric($params[2])) ? intval($params[2])
            : self::AREA_DESC_LENGTH;

        if(in_array('deny-blank', $this->_options)){
            $this->_view->denyBlank = true;
        }

        $this->_view->toasterOptions = array('websiteUrl' => $this->_toasterOptions['websiteUrl']);

        // Adding cache tag for this fa
        array_push($this->_cacheTags, 'fa_'.$params[0]);
        array_push($this->_cacheTags, 'pageTags');
        $areaPages = $featuredArea->getPages();
        foreach ($areaPages as $page) {
            array_push($this->_cacheTags, 'pageid_'.$page->getId());
        }

        $confiHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('config');
        $websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');

        $websiteUrlMediaServer = ($confiHelper->getConfig('mediaServers') ? Tools_Content_Tools::applyMediaServers($websiteHelper->getUrl()) : $websiteHelper->getUrl());

        $this->_view->websiteUrlMediaServer = $websiteUrlMediaServer;

        $loadingLazy = 'loading="lazy"';
        if(in_array('disablelazy', $params)) {
            $loadingLazy = '';
        }

        $this->_view->lazyLoad = $loadingLazy;

        return $this->_view->render('area.phtml');
    }

    private function _renderFeaturedPage($params)
    {
        if (!is_array($params)
            || empty($params)
            || !isset($params[0])
            || !$params[0]
            || preg_match('~^\s*$~', $params[0])
        ) {
            if (Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_CONTENT)) {
                throw new Exceptions_SeotoasterWidgetException($this->_translator->translate(
                    'Featured page id required.'
                ));
            }
            return '';
        }
        if (($page = Application_Model_Mappers_PageMapper::getInstance()->find(intval($params[0]))) === null) {
            if (Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_CONTENT)) {
                throw new Exceptions_SeotoasterWidgetException(
                    $this->_translator->translate('Page with such id is not found')
                );
            }
            return '';
        }

        $this->_view->page       = $page;
        $class                   = current(preg_grep('/class=*/', $params));
        $this->_view->listClass  = ($class !== null) ? preg_replace('/class=/', '', $class) : '';
        $this->_view->descLength = (isset($params[1]) && is_numeric($params[1])) ? intval($params[1])
            : self::AREA_DESC_LENGTH;
        array_push($this->_cacheTags, 'pageid_'.$page->getId());

        $confiHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('config');
        $websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');

        $websiteUrlMediaServer = ($confiHelper->getConfig('mediaServers') ? Tools_Content_Tools::applyMediaServers($websiteHelper->getUrl()) : $websiteHelper->getUrl());

        $this->_view->websiteUrlMediaServer = $websiteUrlMediaServer;

        return $this->_view->render('page.phtml');
    }

    /**
     * Render farea tags(names) for current page
     * If with "filterable" param display links list with names
     * @return string
     */
    private function _renderFeaturedTags()
    {
        $fareaMapper = Application_Model_Mappers_FeaturedareaMapper::getInstance();
        $request = Zend_Controller_Action_HelperBroker::getStaticHelper('response')->getRequest();
        $where = $fareaMapper->getDbTable()->getAdapter()->quoteInto('pf.page_id = ?',
            $this->_toasterOptions['id']);
        $select = $fareaMapper->getDbTable()->select()->from(array('fa' => 'featured_area'), array('fa.name'))
            ->setIntegrityCheck(false)
            ->joinLeft(array('pf' => 'page_fa'), 'fa_id=fa.id')->where($where)->group('fa.name');

        $result = $fareaMapper->getDbTable()->fetchAll($select);
        $fareaTagsExists = $result->toArray();
        if (!empty($fareaTagsExists) && $this->_filterable) {
            $pnum = intval(filter_var($request->getParam('fanum', 0), FILTER_SANITIZE_NUMBER_INT));
            $pageUrl = filter_var($request->getParam('page'), FILTER_SANITIZE_STRING);
            if (isset($this->_toasterOptions['fareaNamesSearch'])) {
                $fareaNamesSearch = strtolower($this->_toasterOptions['fareaNamesSearch']);
                $fareaTagsExists = array_filter($fareaTagsExists, function ($faName) use ($fareaNamesSearch) {
                    if (in_array(strtolower($faName['name']), explode(',', $fareaNamesSearch))) {
                        return $faName['name'];
                    }
                });
            }
            $fareaFilterName = (!empty($this->_toasterOptions['fareaFilterName']) ? filter_var($this->_toasterOptions['fareaFilterName'],
                FILTER_SANITIZE_STRING) : '');
            $this->_view->fareaFilterName = $fareaFilterName;
            $this->_view->pnum = $pnum;
            $this->_view->tags = $fareaTagsExists;
            $this->_view->pageUrl = $pageUrl;

            return $this->_view->render('farea-tags.phtml');
        } elseif (!empty($fareaTagsExists)) {
            return implode(',', array_map(function ($tag) {
                return $tag['name'];
            }, $fareaTagsExists));
        } else {
            return '';
        }
    }

    /**
     * Filter farea by names
     *
     * @param integer $limit query limit
     * @param string $fareaNames farea names
     * @return string parsed content
     */
    private function _filterFa($limit, $fareaNames)
    {
        $fareaMapper = Application_Model_Mappers_FeaturedareaMapper::getInstance();
        $request = Zend_Controller_Action_HelperBroker::getStaticHelper('response');
        $uniqueName = md5($fareaNames);
        $fareaTag = filter_var($request->getRequest()->getParam('tag'), FILTER_SANITIZE_STRING);
        $fareaFilterName = filter_var($request->getRequest()->getParam('fareaName'), FILTER_SANITIZE_STRING);
        $pnum = intval(filter_var($request->getRequest()->getParam('fanum', 0), FILTER_SANITIZE_NUMBER_INT));
        if ($fareaTag && $uniqueName === $fareaFilterName) {
            $fareaNamesSearch = $fareaTag;
        } else {
            $fareaNamesSearch = $fareaNames;
            $fareaTag = '';
        }
        $order = self::FEATURED_FILTER_BY_ID;
        $orderType = 'ASC';
        if ($this->_order) {
            $customOrder = preg_replace('/order=/', '', $this->_order);
            if (in_array($customOrder, array('header_title', 'id', 'h1', 'last_update'))) {
                $order = $customOrder;
            }
        }
        if ($this->_orderType) {
            $customOrderType = preg_replace('/orderType=/', '', $this->_orderType);
            if (in_array($customOrderType, array('ASC', 'DESC'))) {
                $orderType = $customOrderType;
            }
        }
        $where = $fareaMapper->getDbTable()->getAdapter()->quoteInto('fa.name IN (?)', explode(',', $fareaNamesSearch));
        $where .= $fareaMapper->getDbTable()->getAdapter()->quoteInto(' AND p.draft = ?', '0');
        $select = $fareaMapper->getDbTable()->select()->from(array('fa' => 'featured_area'))
            ->setIntegrityCheck(false)
            ->joinLeft(array('pf' => 'page_fa'), 'fa_id=fa.id')
            ->joinLeft(array('p' => 'page'), 'p.id=pf.page_id', null)
            ->joinLeft(array('o' => 'optimized'), 'p.id = o.page_id', null)
            ->columns(
                array(
                    'id' => 'p.id',
                    'previewImage' => 'p.preview_image',
                    'url' => new Zend_Db_Expr('COALESCE(o.url, p.url)'),
                    'h1' => new Zend_Db_Expr('COALESCE(o.h1, p.h1)'),
                    'navName' => new Zend_Db_Expr('COALESCE(o.nav_name, p.nav_name)'),
                    'headerTitle' => new Zend_Db_Expr('COALESCE(o.header_title, p.header_title)'),
                    'metaKeywords' => new Zend_Db_Expr('COALESCE(o.meta_keywords, p.meta_keywords)'),
                    'metaDescription' => new Zend_Db_Expr('COALESCE(o.meta_description, p.meta_description)'),
                    'teaserText' => new Zend_Db_Expr('COALESCE(o.teaser_text, p.teaser_text)'),
                    'templateId' => 'p.template_id',
                    'parentId' => 'p.parent_id',
                    'showInMenu' => 'p.show_in_menu',
                    'lastUpdate' => 'p.last_update',
                    'order' => 'p.order',
                    'targetedKeyPhrase' => 'p.targeted_key_phrase',
                    'siloId' => 'p.silo_id',
                    'system' => 'p.system',
                    'draft' => 'p.draft',
                    'news' => 'p.news',
                    'publishAt' => 'p.publish_at',
                    'externalLinkStatus' => 'p.external_link_status',
                    'externalLink' => 'p.external_link'
                )
            )
            ->where($where)->order(array('p.' . $order . ' ' . $orderType));
        $adapter = new Zend_Paginator_Adapter_DbSelect($select);
        $fareaPaginator = new Zend_Paginator($adapter);
        if ($pnum && $uniqueName === $fareaFilterName) {
            $offset = $limit * ($pnum - 1);
        } else {
            $offset = 0;
            $pnum = 0;
        }
        $fareaPaginator->setItemCountPerPage($limit);
        $fareaPaginator->setCurrentPageNumber($pnum);

        $fareaItems = $adapter->getItems($offset, $limit);
        if (empty($fareaItems)) {
            if (Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_CONTENT)) {
                return $this->_translator->translate('There are no fareas');
            }

            return '';
        }
        $pageUrl = $this->_toasterOptions['url'];
        if ($pageUrl === 'index.html') {
            $pageUrl = '';
        }

        if (!empty($this->_toasterOptions['extraOptions'])) {
            if (in_array('newslog', Tools_Plugins_Tools::getEnabledPlugins(true)) && in_array('option_newsindex',
                    $this->_toasterOptions['extraOptions'])
            ) {
                $pageUrl = Newslog_Models_Mapper_ConfigurationMapper::getInstance()->fetchConfigParam('folder');
                $pageUrl = trim($pageUrl, '/') . '/';
            }
        }

        $pager = $this->_view->paginationControl($fareaPaginator, 'Sliding', 'pager.phtml',
            array(
                'urlData' => $this->_view->websiteUrl . $pageUrl,
                'fareaUniqueName' => $uniqueName,
                'fareaTag' => $fareaTag
            )
        );

        $content = '';
        foreach ($fareaItems as $page) {
            $page['fareaNamesSearch'] = $fareaNamesSearch;
            $page['fareaFilterName'] = $uniqueName;
            $parser = new Tools_Content_Parser($this->_view->tmplFaContent, $page);
            $content .= $parser->parseSimple();
        }

        return $content . $pager;
    }

    public static function getWidgetMakerContent()
    {
        $translator    = Zend_Registry::get('Zend_Translate');
        $view          = new Zend_View(array('scriptPath' => dirname(__FILE__).'/views'));
        $websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
        $data          = array(
            'title'   => $translator->translate('List pages by tags'),
            'icons'   => array($websiteHelper->getUrl() . 'system/images/widgets/featured.png'),
            'content' => $view->render('wmcontent.phtml')
        );
        unset($view, $translator);

        return $data;
    }
}
