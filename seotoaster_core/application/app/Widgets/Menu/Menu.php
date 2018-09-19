<?php

/**
 * Menu
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Widgets_Menu_Menu extends Widgets_Abstract {

    private $_menuTemplate = null;

    protected function  _init() {
        $this->_cacheTags = array(strtolower(__CLASS__));
        $this->_cacheId   = strtolower(__CLASS__).'_lifeTime_'.$this->_cacheLifeTime;
        $this->_widgetId  .= '-role-'.Zend_Controller_Action_HelperBroker::getStaticHelper('Session')->getCurrentUser()->getRoleId();
        $this->_view      = new Zend_View(array(
            'scriptPath' => dirname(__FILE__) . '/views'
        ));
    }

    protected function  _load() {
        $website = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
        $menuType = $this->_options[0];
        if (!empty($this->_options[1])) {

            $developerMode = Zend_Controller_Action_HelperBroker::getStaticHelper('config')->getConfig('enableDeveloperMode');

            // if developerMode = 1, parsing template directly from files
            if ((bool) $developerMode) {
                $websitePath  = $this->_toasterOptions['websitePath'];
                $themePath    = $this->_toasterOptions['themePath'];
                $currentTheme = $this->_toasterOptions['currentTheme'];
                $templatePath = $websitePath.$themePath.$currentTheme.DIRECTORY_SEPARATOR.$this->_options[1].'.html';
                if (file_exists($templatePath)) {
                    $this->_menuTemplate =  Tools_Filesystem_Tools::getFile($templatePath);
                }
            }else {
                $this->_menuTemplate = Application_Model_Mappers_TemplateMapper::getInstance()->find(
                    $this->_options[1]
                );
                if ($this->_menuTemplate instanceof Application_Model_Models_Template) {
                    array_push($this->_cacheTags, $this->_menuTemplate->getName());
                    $this->_menuTemplate = $this->_menuTemplate->getContent();
                }
            }
        }
        $rendererName = '_render' . ucfirst($menuType) . 'Menu';
        $this->_view->websiteUrl = $website->getUrl();
        if (method_exists($this, $rendererName)) {
            return $this->$rendererName();
        }
        throw new Exceptions_SeotoasterException('Can not render <strong>' . $menuType . '</strong> menu.');
    }

    private function _renderMainMenu() {
        $pagesList = array();
        $pages = Application_Model_Mappers_PageMapper::getInstance()->fetchAllMainMenuPages();
        $configHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('config');
        $showMemberPages = (boolean)$configHelper->getConfig('memPagesInMenu');
        $isAllowed = Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_PAGE_PROTECTED);

        $isPageProtected = function ($page) use ($isAllowed, $showMemberPages) {
            if (is_array($page['extraOptions']) && in_array(
                        Application_Model_Models_Page::OPT_PROTECTED,
                        $page['extraOptions']
                    )
                    && !$isAllowed && !$showMemberPages
            ) {
                return true;
            }
            return false;
        };

        $pagesList = array_filter(
            $pages,
            function ($page) use ($isPageProtected) {
                return (!$isPageProtected(
                            $page
                        ) && $page['parentId'] == Application_Model_Models_Page::IDCATEGORY_CATEGORY);
            }
        );

        $newslogEnabledPlugin = false;

        if(in_array('newslog', Tools_Plugins_Tools::getEnabledPlugins(true))) {
            $newslogEnabledPlugin = true;
        }

        foreach ($pagesList as $key => &$catPage) {
            $catId = $catPage['id'];
            if(!empty($newslogEnabledPlugin) && !empty($catPage['extraOptions']) && in_array('option_newsindex', $catPage['extraOptions'])) {
                $newsFolderUrl = Newslog_Models_Mapper_ConfigurationMapper::getInstance()->fetchConfigParam('folder');
                if(!empty($newsFolderUrl)) {
                    $newsFolderUrl = trim($newsFolderUrl, '/') . '/';
                    $pagesList[$key]['url'] = $newsFolderUrl;
                }
            }

            $catPage['subPages'] = array_filter(
                $pages,
                function ($page) use ($isPageProtected, $catId) {
                    return ($page['parentId'] == $catId && !$isPageProtected($page));
                }
            );
        }

        if (is_null($this->_menuTemplate)) {
            $this->_view->pages = $pagesList;
            return $this->_view->render('mainmenu.phtml');
        } else {
            return self::renderMenuTemplate($pagesList, $this->_menuTemplate);
        }
    }

    private function _renderFlatMenu() {
        $flatMenuPages = Application_Model_Mappers_PageMapper::getInstance()->fetchAllStaticMenuPages();
        if (is_array($flatMenuPages) && !empty($flatMenuPages)) {
            $newslogEnabledPlugin = false;

            if(in_array('newslog', Tools_Plugins_Tools::getEnabledPlugins(true))) {
                $newslogEnabledPlugin = true;
            }

            foreach ($flatMenuPages as $key => $page) {
                $extraOptions = $page->getExtraOptions();
                if(!empty($newslogEnabledPlugin) && !empty($extraOptions) && in_array('option_newsindex', $extraOptions)) {
                    $newsFolderUrl = Newslog_Models_Mapper_ConfigurationMapper::getInstance()->fetchConfigParam('folder');
                    if(!empty($newsFolderUrl)) {
                        $newsFolderUrl = trim($newsFolderUrl, '/') . '/';
                        $flatMenuPages[$key]->setUrl($newsFolderUrl);
                    }
                }
            }

            $this->_view->staticPages = $flatMenuPages;
            return $this->_view->render('staticmenu.phtml');
        }
        return '';
    }

    public static function getAllowedOptions() {
        $translator = Zend_Registry::get('Zend_Translate');
        return array(
            array(
                'alias'  => $translator->translate('Main menu'),
                'option' => 'menu:main'
            ),
            array(
                'alias'  => $translator->translate('Flat menu'),
                'option' => 'menu:flat'
            )
        );
    }

    /**
     * @deprecated
     */
    private function _isPageProtected($page) {
        return (is_array($page['extraOptions']) && in_array(
                    Application_Model_Models_Page::OPT_PROTECTED,
                    $page['extraOptions']
                )) ? true : false;
    }

    public static function renderMenuTemplate($pages, $template, $parentCategoryPage = null) {
        $entityParser = new Tools_Content_EntityParser();
        $website = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
        $dictionary = array();

        if ($parentCategoryPage === null) {
            $menuHtml = '<ul class="main_menu" >';
        } else {
            $menuHtml = '<ul>';
        }

        $i = 1;
        foreach ($pages as $k => $page) {
            $menuItemTemplate = $template;
            $dictionary['$page:preview'] = '<img class="page-teaser-image" src="' . Tools_Page_Tools::getPreview(
                        intval($page['id'])
                    ) . '" alt="index">';
            if ($parentCategoryPage === true) {
                $dictionary['$page:category:name'] = $parentCategoryPage['h1'];
            }
            $dictionary['$page:target_blank'] = '';
            foreach ($page as $prop => $item) {
                if (is_array($item)) {
                    continue;
                }
                if ($prop === 'url') {
                    $item = $website->getUrl() . str_replace('index.html', '', $item);
                    if ($page['external_link_status'] === '1'){
                        $item = $page['external_link'];
                        $dictionary['$page:target_blank'] = 'target=_blank';
                    }
                }
                $dictionary['$page:' . $prop] = $item;
                $dictionary['$page:' . $prop . ':clear'] = strip_tags($item);
            }

            if (!empty($page['subPages'])) {
                $menuItemTemplate = preg_replace_callback(
                    '~{submenu}(.*){/submenu}~siuU',
                    function ($match) use ($page) {
                        return Widgets_Menu_Menu::renderMenuTemplate($page['subPages'], $match[1], $page);
                    },
                    $template
                );
                $menuItemTemplate = preg_replace('~{ifpages}(.*){/ifpages}~siuU', '$1', $menuItemTemplate);
            } else {
                $menuItemTemplate = preg_replace('~{(submenu|ifpages)}.*{/(submenu|ifpages)}~siuU', '', $template);
            }

            $menuHtml .= '<li class="' . ($parentCategoryPage === null ? 'category cat-' . ($i++) : 'page') . '">';
            $menuHtml .= $entityParser->setDictionary($dictionary)->parse($menuItemTemplate);
            $menuHtml .= '</li>';
        }

        $menuHtml .= '</ul>';

        return $menuHtml;
    }
}

