<?php

class Application_Model_Models_Template extends Application_Model_Models_Abstract {

	const ID_DEFAULT    = 'default';

	const ID_INDEX      = 'index';

	const ID_CATEGORY   = 'category';

	const ID_NEWS       = 'news';

	const ID_PRODUCT    = 'product';

	const TYPE_REGULAR  = 'typeregular';

	const TYPE_PRODUCT  = 'typeproduct';

	const TYPE_LISTING  = 'typelisting';

	const TYPE_CHECKOUT = 'typecheckout';

	const TYPE_MAIL     = 'typemail';

	const TYPE_QUOTE    = 'typequote';

	protected $_name         = '';

	protected $_content      = '';

	protected $_previewImage = '';

	protected $_type         = self::TYPE_REGULAR;

	public function getName() {
		return $this->_name;
	}

	public function setName($name) {
		$this->_name = $name;
		return $this;
	}

	public function getContent() {
		return $this->_content;
	}

	public function setContent($content) {
		$this->_content = $content;
		return $this;
	}

	public function setPreviewImage($previewImage) {
		$this->_previewImage = $previewImage;
		return $this;
	}

	public function getPreviewImage() {
		return $this->_previewImage;
	}

	public function getType() {
		return $this->_type;
	}

	/**
	 * Set template type.
	 * Use constants of the Application_Model_Models_Template for valid types
	 *
	 * @param string $type can be regular | product | listing | mail
	 * @return Application_Model_Models_Template
	 */
	public function setType($type) {
		$this->_type = $this->_validateType($type);
		return $this;
	}

	private function _validateType($type) {
		$validTypes = array(
			self::TYPE_REGULAR,
			self::TYPE_PRODUCT,
			self::TYPE_CHECKOUT,
			self::TYPE_LISTING,
			self::TYPE_MAIL,
			self::TYPE_QUOTE
		);
		if(!in_array($type, $validTypes)) {
			throw new Exceptions_SeotoasterTemplateException('Wrong template type.');
		}
		return $type;
	}
}

