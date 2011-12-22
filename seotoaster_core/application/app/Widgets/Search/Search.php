<?php

/**
 * Search
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Widgets_Search_Search extends Widgets_Abstract {

	const INDEX_FOLDER      = 'search';

	private $_websiteHelper = null;

	protected function _init() {
		parent::_init();
		$this->_view = new Zend_View(array(
			'scriptPath' => dirname(__FILE__) . '/views'
		));
		$this->_websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
	}

	protected function _load() {
		if(!is_array($this->_options) || empty($this->_options) || !isset($this->_options[0]) || !$this->_options[0] || preg_match('~^\s*$~', $this->_options[0])) {
			throw new Exceptions_SeotoasterWidgetException($this->_translator->translate('Not enough parameters'));
		}
		$rendererName = '_renderSearch' . ucfirst(array_shift($this->_options));
		if(method_exists($this, $rendererName)) {
			return $this->$rendererName($this->_options);
		}
	}

	private function _renderSearchForm() {
		if(!isset($this->_options[0]) || !intval($this->_options[0])) {
			throw new Exceptions_SeotoasterWidgetException($this->_translator->translate('Not enough parameters'));
		}
		$searchForm = new Application_Form_Search();
		$searchForm->setResultsPageId($this->_options[0])
			->setAction($this->_websiteHelper->getUrl() . 'backend/search/search/');

		$this->_view->searchForm = $searchForm;
		$this->_view->renewIndex = Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_ADMINPANEL);
		return $this->_view->render('form.phtml');
	}

	private function _renderSearchResults() {
		$sessionHelper             = Zend_Controller_Action_HelperBroker::getStaticHelper('session');
		$this->_view->useImage     = (isset($this->_options[0]) && ($this->_options[0] == 'img' || $this->_options[0] == 'imgc')) ? $this->_options[0] : false;
		$this->_view->hits         = $sessionHelper->searchHits;
		$sessionHelper->searchHits = null;
		return $this->_view->render('results.phtml');
	}

	public static function getWidgetMakerContent() {
		$view = new Zend_View(array(
			'scriptPath' => dirname(__FILE__) . '/views'
		));

		$data = array(
			//'title'   => 'Search engine',
			'content' => $view->render('wmcontent.phtml')
		);

		unset($view);
		return $data;
	}
}

