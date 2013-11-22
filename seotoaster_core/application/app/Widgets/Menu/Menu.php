<?php

/**
 * Menu
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Widgets_Menu_Menu extends Widgets_Abstract {

    private $_menuTemplate = null;

	protected function  _init() {
		$this->_cacheTags = array(__CLASS__);
		$this->_cacheId   = strtolower(__CLASS__).(!empty($this->_options)?'-'.implode('-', $this->_options):'');
		$this->_view = new Zend_View(array(
			'scriptPath' => dirname(__FILE__) . '/views'
		));
	}

	protected function  _load() {
        $website      = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
		$menuType     = $this->_options[0];
        if(isset($this->_options[1]) && !empty($this->_options[1])) {
            $this->_menuTemplate = Application_Model_Mappers_TemplateMapper::getInstance()->find($this->_options[1]);
            if($this->_menuTemplate !== null && $this->_menuTemplate->getType() === Application_Model_Models_Template::TYPE_MENU) {
                array_push($this->_cacheTags, $this->_menuTemplate->getName());
                $this->_menuTemplate = $this->_menuTemplate->getContent();
            }
        }
		$rendererName = '_render' . ucfirst($menuType) . 'Menu';
        $this->_view->websiteUrl = $website->getUrl();
		if(method_exists($this, $rendererName)) {
			return $this->$rendererName();
		}
		throw new Exceptions_SeotoasterException('Can not render <strong>' . $menuType . '</strong> menu.');
	}

	private function _renderMainMenu() {
        $pagesList       = array();
        $pages           = Application_Model_Mappers_PageMapper::getInstance()->fetchAllMainMenuPages();
        $configHelper    = Zend_Controller_Action_HelperBroker::getStaticHelper('config');
        $showMemberPages = (boolean) $configHelper->getConfig('memPagesInMenu');
        $isAllowed       = Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_PAGE_PROTECTED);

		$isPageProtected = function($page) use ($isAllowed, $showMemberPages){
			if (is_array($page['extraOptions']) && in_array(Application_Model_Models_Page::OPT_PROTECTED, $page['extraOptions'])
					&& !$isAllowed && !$showMemberPages) {
				return true;
			}
			return false;
		};

		$pagesList = array_filter($pages, function($page) use ($isPageProtected){
			return (!$isPageProtected($page) && $page['parentId'] == Application_Model_Models_Page::IDCATEGORY_CATEGORY);
		});

		foreach ($pagesList as &$catPage) {
			$catId = $catPage['id'];
			$catPage['subPages'] = array_filter($pages, function($page) use ($isPageProtected, $catId) {
				return ($page['parentId'] == $catId && !$isPageProtected($page));
			});
		}

        if(is_null($this->_menuTemplate)) {
            $this->_view->pages = $pagesList;
            return $this->_view->render('mainmenu.phtml');
        } else {
            return $this->_processTemplateMenu($pagesList);

        }
	}

	private function _renderFlatMenu() {
        $flatMenuPages = Application_Model_Mappers_PageMapper::getInstance()->fetchAllStaticMenuPages();
        if($flatMenuPages && is_array($flatMenuPages) && !empty($flatMenuPages)) {
            $this->_view->staticPages = Application_Model_Mappers_PageMapper::getInstance()->fetchAllStaticMenuPages();
            return $this->_view->render('staticmenu.phtml');
        }
        return '';
	}

	public static function getAllowedOptions() {
		$translator = Zend_Registry::get('Zend_Translate');
		return array(
			array(
				'alias'   => $translator->translate('Main menu'),
				'option' => 'menu:main'
			),
			array(
				'alias'   => $translator->translate('Flat menu'),
				'option' => 'menu:flat'
			)
		);
	}

	/**
	 * @deprecated
	 */
	private function _isPageProtected($page) {
        return (is_array($page['extraOptions']) && in_array(Application_Model_Models_Page::OPT_PROTECTED, $page['extraOptions'])) ? true : false;
    }

    private function _processTemplateMenu($pages, $subMenu = false, $categoryPage = null) {
        $entityParser = new Tools_Content_EntityParser();
        $dictionary = array();
        $class = ($subMenu === false) ? 'main_menu' : '';
        if($subMenu === false) {
            $template = $this->_menuTemplate;
            $menuHtml = '<ul class="'.$class.'">';
        } else {
            preg_match('/\{submenu\}((.)*)\{\/submenu\}/msiu', $this->_menuTemplate, $matches);
            $template = $matches[1];
            $menuHtml = '<ul>';
        }
        foreach($pages as $k => $page) {
            $dictionary['$page:preview'] = '<img class="page-teaser-image" src="'.Tools_Page_Tools::getPreview((integer)$page['id']).'" alt="index">';
            if($subMenu === true) {
                $dictionary['$page:category:name'] = $categoryPage['h1'];
            }
            foreach($page as $prop => $item) {
                $dictionary['$page:'.$prop] = $item;
            }
            $entityParser->setDictionary($dictionary);
            $class = ($subMenu === false) ? 'category cat-'.($k+1) : 'page';
            $menuHtml .= '<li class="'.$class.'">';
            $menuHtml .= $entityParser->parse($template);
            if(isset($page['subPages']) && !empty($page['subPages'])) {
                $menuHtml = preg_replace('/\{submenu\}(.)*\{\/submenu\}/msiu', $this->_processTemplateMenu($page['subPages'], true, $page), $menuHtml);
            }
            else {
                $menuHtml = preg_replace('/\{submenu\}(.)*\{\/submenu\}/msiu', '', $menuHtml);
            }
            $menuHtml .= '</li>';
        }
        return $menuHtml.'</ul>';
    }
}

