<?php

/**
 * Deeplink
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Application_Form_Deeplink extends Zend_Form {

	protected $_anchorText   = '';

	protected $_url          = '';

	protected $_toasterPages = array();

	protected $_nofollow     = false;

	public function init() {

		$this->setMethod(Zend_Form::METHOD_POST)
			 ->setAttrib('class', '_fajax')
			 ->setAttrib('data-callback', 'reloadDeeplinksList');

		$this->addElement(new Zend_Form_Element_Text(array(
			'id'         => 'anchor-text',
			'name'       => 'anchorText',
			'label'      => 'Anchor text',
			'value'      => $this->_anchorText,
			'required'   => true,
			'validators' => array(
				new Zend_Validate_Db_NoRecordExists(array(
					'table' => 'deeplink',
					'field' => 'name'
				))
			),
			'filters'    => array('StringTrim')
		)));

		/*$this->addElement(new Zend_Form_Element_Radio(array(
			'name'         => 'urlType',
			'multiOptions' => array(
				0 => 'Local url',
				1 => 'External url'
			),
			//'label'     => 'Url',
			'required'  => true,
			'separator' => '',
			'value'     => 'local'
		)));*/

		$this->addElement(new Zend_Form_Element_Checkbox(array(
			'name'    => 'urlType',
			//'label'   => 'Local url',
			'required'  => true,
			'value'   => 'local',
			'checked' => 'checked'
		)));

		$this->addElement(new Zend_Form_Element_Select(array(
			'name'                     => 'url',
			'id'                       => 'url',
			'value'                    => $this->_url,
			'class'                    => '_tdropdown',
			//'label'                    => 'Select page',
			'filters'                  => array(
				new Zend_Filter_StringTrim(),
				new Filters_UrlScheme()
			),
			'registerInArrayValidator' => false
		)));
        $this->getElement('url')->setDisableTranslator(true);

		$this->addElement(new Zend_Form_Element_Checkbox(array(
			'name'    => 'nofollow',
			'id'      => 'nofollow',
			'label'   => 'No follow?',
			'value'   => $this->_nofollow,
			'checked' => ($this->_nofollow) ? 'checked' : ''
		)));

		$this->addElement(new Zend_Form_Element_Button(array(
			'name'  => 'addDeeplink',
			'id'    => 'add-deeplink',
			'class' => 'btn ticon-plus grid_2 omega',
			'value' => 'Add deeplink',
			'label' => 'Add deeplink',
            'type'  => 'submit'
		)));

		$this->setElementDecorators(array('ViewHelper', 'Label'));

		$this->getElement('addDeeplink')->setDecorators(array('ViewHelper'));
	}

	public function getAnchorText() {
		return $this->_anchorText;
	}

	public function setAnchorText($anchorText) {
		$this->_anchorText = $anchorText;
		$this->getElement('anchorText')->setValue($anchorText);
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

	public function getToasterPages() {
		return $this->_toasterPages;
	}

	public function setToasterPages($toasterPages) {
		$this->_toasterPages = $toasterPages;
		$this->getElement('url')->setMultioptions($toasterPages);
		return $this;
	}

	public function getNofollow() {
		return $this->_nofollow;
	}

	public function setNofollow($nofollow) {
		$this->_nofollow = $nofollow;
	}


}

