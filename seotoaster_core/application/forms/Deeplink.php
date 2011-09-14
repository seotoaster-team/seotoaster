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

		$this->addElement(new Zend_Form_Element_Select(array(
			'name'                     => 'url',
			'id'                       => 'url',
			'value'                    => $this->_url,
			'class'                    => '_tdropdown',
			'label'                    => 'Select page',
			'registerInArrayValidator' => false
		)));

		$this->addElement(new Zend_Form_Element_Checkbox(array(
			'name'    => 'nofollow',
			'id'      => 'nofollow',
			'label'   => 'No follow?',
			'value'   => $this->_nofollow,
			'checked' => ($this->_nofollow) ? 'checked' : ''
		)));

		$this->addElement(new Zend_Form_Element_Submit(array(
			'name'  => 'addDeeplink',
			'id'    => 'add-deeplink',
			'value' => 'Add deeplink',
			'label' => 'Add deeplink'
		)));
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

