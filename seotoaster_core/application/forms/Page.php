<?php

class Application_Form_Page extends Application_Form_Secure {

	protected $_h1              = '';

	protected $_headerTitle     = '';

	protected $_url             = '';

	protected $_navName         = '';

	protected $_metaDescription = '';

	protected $_metaKeywords    = '';

	protected $_teaserText      = '';

	protected $_inMenu          = '';

	protected $_is404page       = false;

	protected $_protected       = false;

	protected $_parentId        = false;

	protected $_templateId      = '';

	protected $_pageId          = '';

	protected $_draft           = false;

	protected $_publishAt       = '';

	protected $_pageOption      = 0;

    protected $_externalLinkStatus = 0;

    protected $_externalLink = '';

	public function init() {
        parent::init();
        $this->setMethod(Zend_Form::METHOD_POST);

		$this->addElement(new Zend_Form_Element_Text(array(
			'id'       => 'h1',
			'name'     => 'h1',
			'label'    => 'Page header H1 tag',
			'value'    => $this->_h1,
			'required' => true,
			'filters'  => array('StringTrim')
		)));

		$this->addElement(new Zend_Form_Element_Text(array(
			'id'       => 'header-title',
			'name'     => 'headerTitle',
			'label'    => 'Display in browser title as',
			'value'    => $this->_headerTitle,
			'required' => true,
			'filters'  => array('StringTrim')
		)));

		$this->addElement(new Zend_Form_Element_Text(array(
			'id'       => 'url',
			'name'     => 'url',
			'label'    => 'Page URL in address bar',
			'value'    => $this->_url,
			'required' => true,
			'filters'  => array(
				new Zend_Filter_StringTrim('.')
			)
		)));

		$this->addElement(new Zend_Form_Element_Text(array(
			'id'       => 'nav-name',
			'name'     => 'navName',
			'label'    => 'Display in navigation as',
			'value'    => $this->_navName,
			'required' => true,
			'filters'  => array('StringTrim')
		)));

		$this->addElement(new Zend_Form_Element_Text(array(
			'name'     => 'metaKeywords',
			'id'       => 'meta-keywords',
			'label'    => 'Meta keywords',
			'value'    => $this->_metaKeywords,
			'filters'  => array('StringTrim')
		)));

		$this->addElement(new Zend_Form_Element_Textarea(array(
			'name'     => 'metaDescription',
			'id'       => 'meta-description',
			'cols'     => '45',
			'rows'     => '4',
			'label'    => 'Meta description',
			'value'    => $this->_metaDescription,
			'filters'  => array('StringTrim')
		)));

		$this->addElement(new Zend_Form_Element_Textarea(array(
			'name'     => 'teaserText',
			'id'       => 'teaser-text',
			'cols'     => '45',
			'rows'     => '6',
			'value'    => $this->_teaserText,
			'filters'  => array('StringTrim')
		)));

		$this->addElement(new Zend_Form_Element_Radio(array(
			'name' => 'inMenu',
			'multiOptions' => array(
				Application_Model_Models_Page::IN_MAINMENU   => 'Main Menu',
				Application_Model_Models_Page::IN_STATICMENU => 'Flat Menu',
				Application_Model_Models_Page::IN_NOMENU     => 'No Menu'
			),
			'label'     => 'Navigation',
			'required'  => true,
			'separator' => ''
		)));

		$this->addElement(new Zend_Form_Element_Select(array(
			'name'         => 'pageCategory',
			'id'           => 'page-category',
			'label'        => 'Main menu',
			'class'        => 'mb5px',
			'multiOptions' => array(
				'Seotoaster' => array(
					Application_Model_Models_Page::IDCATEGORY_CATEGORY => 'This page is a category'
				)
			),
			'registerInArrayValidator' => false,
            'required' => true
		)));

        // Disabling translator for the list of categories
        $this->getElement('pageCategory')->setDisableTranslator(true);

		$this->addElement(new Zend_Form_Element_Select(array(
			'name'         => 'extraOptions',
			'id'           => 'page-options',
			'class'        => 'grid_8 alpha omega',
			'multiOptions' => array_merge(array('0' => 'Select an option'), Tools_Page_Tools::getPageOptions(true)),
			'registerInArrayValidator' => false,
			'value' => $this->_pageOption
		)));

		$this->addElement(new Zend_Form_Element_Hidden(array(
			'id'       => 'templateId',
			'name'     => 'templateId',
			'required' => true,
			'label'    => 'Current template',
			'value'    => $this->_templateId
		)));

		$this->addElement(new Zend_Form_Element_Hidden(array(
			'id'    => 'pageId',
			'name'  => 'pageId',
			'value' => $this->_pageId
		)));

		$this->addElement(new Zend_Form_Element_Hidden(array(
			'id'    => 'draft',
			'name'  => 'draft',
			'value' => $this->_draft
		)));

		$this->addElement(new Zend_Form_Element_Hidden(array(
			'id'    => 'optimized',
			'name'  => 'optimized'
		)));

		$this->addElement(new Zend_Form_Element_Hidden(array(
			'id'    => 'publish-at',
			'name'  => 'publishAt',
			'value' => $this->_publishAt
		)));

        $this->addElement(
            new Zend_Form_Element_Hidden(array(
                'id' => 'external-link-status',
                'name' => 'externalLinkStatus',
                'value' => $this->_externalLinkStatus
            ))
        );

        $this->addElement(
            new Zend_Form_Element_Hidden(array(
                'id' => 'external-link',
                'name' => 'externalLink',
                'value' => $this->_externalLink,
                'filters' => array(
                    new Zend_Filter_StringTrim()
                )
            ))
        );

		$this->addElement(new Zend_Form_Element_Button(array(
			'name'  => 'updatePage',
			'id'    => 'update-page',
			'type'  => 'submit',
			'value' => 'Save page',
			'class' => 'btn ticon-save mr-grid',
			'label' => 'Save page',
			'escape'=> false
		)));

        $this->addElement(new Zend_Form_Element_Hidden(array(
            'id'    => 'removePreviousOption',
            'name'  => 'removePreviousOption',
            'value' => $this->_removePreviousOption
        )));

		//$this->setDecorators(array('ViewScript'));
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
		$this->_url = preg_replace('~\.[a-z0-9-]+$~ui', '', $url);
		$this->getElement('url')->setValue($this->_url);
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

	public function getParentId() {
		return $this->_parentId;
	}

	public function setParentId($parentId) {
		$this->_parentId = $parentId;
		$this->getElement('pageCategory')->setValue($parentId);
		return $this;
	}

	public function getTemplateId() {
		return $this->_templateId;
	}

	public function setTemplateId($templateId) {
		$this->_templateId = $templateId;
		$this->getElement('templateId')->setValue($templateId);
		return $this;
	}

	public function getPageId() {
		return $this->_pageId;
	}

	public function setPageId($pageId) {
		$this->_pageId = $pageId;
		$this->getElement('pageId')->setValue($pageId);
		return $this;
	}

	public function getDraft() {
		return $this->_draft;
	}

	public function setDraft($draft) {
		$this->_draft = $draft;
		$this->getElement('draft')->setValue($draft);
		return $this;
	}

	public function getPublishAt() {
		return $this->_publishAt;
	}

	public function setPublishAt($publishAt) {
		$this->_publishAt = $publishAt;
		$this->getElement('publishAt')->setValue($publishAt);
		return $this;
	}

	public function getMainMenuOptions() {
		return $this->getElement('pageCategory')->getMultiOptions();
	}

    public function setExternalLinkStatus($externalLinkStatus)
    {
        $this->_externalLinkStatus = $externalLinkStatus;
        $this->getElement('externalLinkStatus')->setValue($externalLinkStatus);
        return $this;
    }

    public function getExternalLinkStatus()
    {
        return $this->_externalLinkStatus;
    }

    public function setExternalLink($externalLink)
    {
        $this->_externalLink = $externalLink;
        $this->getElement('externalLink')->setValue($externalLink);
        return $this;
    }

    public function getExternalLink()
    {
        return $this->_externalLink;
    }

	public function lockField($fieldName) {
		$this->getElement($fieldName)
			->setAttribs(array(
				'disabled' => true,
				'readonly' => true,
                'class'    => 'noedit'
			));
	}

	public function lockFields($fields) {
		foreach($fields as $fieldName) {
			$this->lockField($fieldName);
		}
	}

    /**
     * Page form validation.
     *
     * Along with the standart validation user selection will be validated too
     *
     * @param array $data
     * @return bool
     */
    public function isValid($data) {
        return (($data['pageCategory'] != -4) && parent::isValid($data));
    }


}

