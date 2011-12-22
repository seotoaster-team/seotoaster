<?php

/**
 * Redirect form
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Application_Form_Redirect extends Zend_Form {

	protected $_fromUrl      = '';

	protected $_toUrl        = '';

	protected $_toasterPages = array();


	public function init() {

		$this->setMethod(Zend_Form::METHOD_POST)
			 ->setAttrib('class', '_fajax')
			 ->setAttrib('data-callback', 'reloadRedirectsList');

		$this->addElement(new Zend_Form_Element_Text(array(
			'id'         => 'from-url',
			'name'       => 'fromUrl',
			'label'      => 'Former url',
			'value'      => $this->_fromUrl,
			'required'   => true,
			'validators' => array(
				new Validators_UrlRegex()
			),
			'filters'    => array(
				new Zend_Filter_StringTrim(),
				new Filters_UrlScheme()
			)
		)));

		$this->addElement(new Zend_Form_Element_Radio(array(
			'name'         => 'urlType',
			'multiOptions' => array(
				0 => 'Local url',
				1 => 'External url'
			),
			//'label'     => 'Url',
			'required'  => true,
			'separator' => '',
			'value'     => 'local'
		)));

		$this->addElement(new Zend_Form_Element_Select(array(
			'name'       => 'toUrl',
			'id'         => 'to-url',
			'value'      => $this->_toUrl,
			//'label'      => 'Url',
			'class'      => '_tdropdown',
			'filters'    => array(
				new Zend_Filter_StringTrim(),
				new Filters_UrlScheme()
			),
			'registerInArrayValidator' => false
		)));

		$this->addElement(new Zend_Form_Element_Submit(array(
			'name'  => 'addRedirect',
			'id'    => 'add-redirect',
			'value' => 'Add redirect',
			'label' => 'Add redirect'
		)));

	}

	public function getFromUrl() {
		return $this->_fromUrl;
	}

	public function setFromUrl($fromUrl) {
		$this->_fromUrl = $fromUrl;
		$this->getElement('fromUrl')->setValue($fromUrl);
		return $this;
	}

	public function getToUrl() {
		return $this->_toUrl;
	}

	public function setToUrl($toUrl) {
		$this->_toUrl = $toUrl;
		$this->getElement('toUrl')->setValue($toUrl);
		return $this;
	}

	public function getToasterPages() {
		return $this->_toasterPages;
	}

	public function setToasterPages($toasterPages) {
		$this->_toasterPages = $toasterPages;
		$this->getElement('toUrl')->setMultioptions($toasterPages);
		return $this;
	}



}

