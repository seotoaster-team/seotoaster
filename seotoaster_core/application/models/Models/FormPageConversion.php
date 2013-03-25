<?php

/**
 * FormPageConversion model
 *
 * @author Seotoaster Dev Team
 */
class Application_Model_Models_FormPageConversion extends Application_Model_Models_Abstract {

	protected $_pageId        = 0;

	protected $_formName      = '';
    
    protected $_conversionCode      = '';

	
	public function  __construct(array $options = null) {
		parent::__construct($options);
	}

	public function getPageId() {
		return $this->_pageId;
	}

	public function setPageId($pageId) {
		$this->_pageId = $pageId;
		return $this;
	}

	public function getFormName() {
		return $this->_formName;
	}

	public function setFormName($formName) {
		$this->_formName = $formName;
		return $this;
	}
    
    public function getConversionCode() {
		return $this->_conversionCode;
	}

	public function setConversionCode($conversionCode) {
		$this->_conversionCode = $conversionCode;
		return $this;
	}

}

