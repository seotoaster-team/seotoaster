<?php

class Application_Form_Page extends Zend_Form {

	protected $_h1              = '';

	protected $_headerTitle     = '';

	protected $_url             = '';

	protected $_navName         = '';

	protected $_metaDescription = '';

	protected $_metaKeywords    = '';

	protected $_teaserText      = '';

	protected $_inMenu          = '';

	protected $_is404Page       = false;

	protected $_protected       = false;

	protected $_parentId        = false;

	public function init() {
		$this->setMethod(Zend_Form::METHOD_POST);
			
		//id for the page form defined in the page.phtml
		//$this->setAttrib('id', 'frm-page');

		$this->addElement('text', 'h1', array(
			'id'       => 'h1',
			'label'    => 'Page header H1 tag',
			'value'    => $this->_h1,
			'required' => true,
			'filters'  => array('StringTrim')
		));

		$this->addElement('text', 'headerTitle', array(
			'id'       => 'header-title',
			'label'    => 'Display in browser title as',
			'value'    => $this->_headerTitle,
			'required' => true,
			'filters'  => array('StringTrim')
		));

		$this->addElement('text', 'url', array(
			'id'       => 'url',
			'label'    => 'Page URL in address bar',
			'value'    => $this->_url,
			'required' => true,
			'filters'  => array('StringTrim')
		));

		$this->addElement('text', 'navName', array(
			'id'       => 'nav-name',
			'label'    => 'Display in navigation as',
			'value'    => $this->_navName,
			'required' => true,
			'filters'  => array('StringTrim')
		));

		$this->addElement('textarea', 'metaDescription', array(
			'id'       => 'meta-description',
			'cols'     => '45',
			'rows'     => '7',
			'label'    => 'Meta description',
			'class'    => 'h100',
			'value'    => $this->_metaDescription,
			'filters'  => array('StringTrim')
		));

		$this->addElement('textarea', 'metaKeywords', array(
			'id'       => 'meta-keywords',
			'cols'     => '45',
			'rows'     => '3',
			'label'    => 'Meta keywords',
			'class'    => 'h80',
			'value'    => $this->_metaKeywords,
			'filters'  => array('StringTrim')
		));

		$this->addElement('textarea', 'teaserText', array(
			'id'       => 'teaser-text',
			'cols'     => '45',
			'rows'     => '3',
			'label'    => 'Teaser Text',
			'value'    => $this->_teaserText,
			'class'    => 'hldd00',
			'filters'  => array('StringTrim')
		));

		$this->addElement('radio', 'inMenu', array(
			'multiOptions' => array(
				Application_Model_Models_Page::IN_MAINMENU   => 'Main Menu',
				Application_Model_Models_Page::IN_STATICMENU => 'Static Menu',
				Application_Model_Models_Page::IN_NOMENU     => 'No Menu'
			),
			'required' => true,
			'separator' => '',
		));

		$this->addElement('select', 'pageCategory', array(
			'id' => 'page-category',
			'multiOptions' => array(
				'1' => 'Make your selection',
				'0' => 'This page is a category',
				'2' => 'Draft pages',
			)
		));

		$this->addElement('checkbox', '404Page', array(
			'id'      => '404-page',
			'label'   => 'Not found 404 page',
			'value'   => $this->_is404Page,
			'checked' => ($this->_is404Page) ? 'checked' : ''
		));

		$this->addElement('checkbox', 'protectedPage', array(
			'id'      => 'protected-page',
			'label'   => 'Protected page',
			'value'   => $this->_protected,
			'checked' => ($this->_protected) ? 'checked' : ''
		));

		$this->addElement('submit', 'updatePage', array(
			'id'    => 'update-page',
			'value' => 'Save page'
		));

		$this->setDecorators(array('ViewScript'));
		$this->setElementDecorators(array('ViewHelper', 'Label'));
		$this->getElement('updatePage')->removeDecorator('Label');
	}

	public function getH1() {
		return $this->_h1;
	}

	public function setH1($h1) {
		$this->_h1 = $h1;
		$this->getElement('h1')->setValue($h1);
		return $this;
	}

	public function getHeaderTitle() {
		return $this->_headerTitle;
	}

	public function setHeaderTitle($headerTitle) {
		$this->_headerTitle = $headerTitle;
		$this->getElement('headerTitle')->setValue($headerTitle);
		return $this;
	}

	public function getUrl() {
		return $this->_url;
	}

	public function setUrl($url) {
		$this->_url = $url;
		$this->getElement('url')->setValue($url);
		return $this;
	}

	public function getNavName() {
		return $this->_navName;
	}

	public function setNavName($navName) {
		$this->_navName = $navName;
		$this->getElement('navName')->setValue($navName);
		return $this;
	}

	public function getMetaDescription() {
		return $this->_metaDescription;
	}

	public function setMetaDescription($metaDescription) {
		$this->_metaDescription = $metaDescription;
		$this->getElement('metaDescription')->setValue($metaDescription);
		return $this;
	}

	public function getMetaKeywords() {
		return $this->_metaKeywords;
	}

	public function setMetaKeywords($metaKeywords) {
		$this->_metaKeywords = $metaKeywords;
		$this->getElement('metaKeywords')->setValue($metaKeywords);
		return $this;
	}

	public function getTeaserText() {
		return $this->_teaserText;
	}

	public function setTeaserText($teaserText) {
		$this->_teaserText = $teaserText;
		$this->getElement('teaserText')->setValue($teaserText);
		return $this;
	}

	public function setShowInMenu($showInMenu) {
		$this->_inMenu = $showInMenu;
		return $this;
	}

	public function getShowInMenu() {
		return $this->_inMenu;
	}

	public function getIs404Page() {
		return $this->_is404Page;
	}

	public function setIs404Page($is404Page) {
		$this->_is404Page = $is404Page;
		$this->getElement('404Page')->setValue($is404Page);
		return $this;
	}

	public function getProtected() {
		return $this->_protected;
	}

	public function setProtected($protected) {
		$this->_protected = $protected;
		$this->getElement('protectedPage')->setValue($protected);
		return $this;
	}

	public function getParentId() {
		return $this->_parentId;
	}

	public function setParentId($parentId) {
		$this->_parentId = $parentId;
		$this->getElement('pageCategory')->setValue($parentId);
		return $this;
	}



}

