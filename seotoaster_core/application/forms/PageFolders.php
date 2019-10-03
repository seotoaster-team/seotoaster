<?php

/**
 * Page folders form
 */
class Application_Form_PageFolders extends Application_Form_Secure {

	protected $_pageFolder      = '';

	protected $_indexPage   = '';

	public function init() {
        parent::init();
		$this->setMethod(Zend_Form::METHOD_POST)
			 ->setAttrib('class', '_fajax')
			 ->setAttrib('data-callback', 'reloadPageFolders');

		$this->addElement(new Zend_Form_Element_Text(array(
			'id'         => 'pageFolder',
			'name'       => 'pageFolder',
			'label'      => 'Subfolder name',
			'value'      => $this->_pageFolder,
			'required'   => true,
			'filters'    => array(
				new Zend_Filter_StringTrim(),
                new Zend_Filter_StringToLower()
			)
		)));

		$this->addElement(new Zend_Form_Element_Select(array(
			'name'       => 'indexPage',
			'id'         => 'indexPage',
			'value'      => $this->_indexPage,
			'label'      => 'Folder index page',
			'class'      => '_tdropdown',
			'filters'    => array(
				new Zend_Filter_StringTrim(),
				new Filters_UrlScheme()
			),
			'registerInArrayValidator' => false
		)));

        $this->getElement('pageFolder')->setDisableTranslator(true);

		$this->setElementDecorators(array('ViewHelper', 'Label'));

        $this->addElement(new Zend_Form_Element_Button(array(
            'name'  => 'addFolder',
            'id'    => 'add-folder',
            'value' => 'Add folder',
            'class' => 'btn ticon-plus grid_2 omega',
            'label' => 'Add',
            'type'  => 'submit'
        )));

	}

	public function getPageFolder() {
		return $this->_pageFolder;
	}

	public function setPageFolder($pageFolder) {
		$this->_pageFolder = $pageFolder;
		$this->getElement('pageFolder')->setValue($pageFolder);
		return $this;
	}

	public function getIndexPage() {
		return $this->_indexPage;
	}

	public function setIndexPage($indexPage) {
		$this->_indexPage = $indexPage;
		$this->getElement('indexPage')->setValue($indexPage);
		return $this;
	}
}

