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

	const TYPE_NEWS_LISTING  = 'type_news_list';

	const TYPE_CHECKOUT = 'typecheckout';

	const TYPE_MAIL     = 'typemail';

	const TYPE_MOBILE   = 'typemobile';

    const TYPE_MENU     = 'typemenu';

	protected $_name         = '';

	protected $_oldName      = null;

	protected $_content      = '';

	protected $_type         = self::TYPE_REGULAR;

	/**
	 * Return template name
	 * @return string
	 */
	public function getName() {
		return $this->_name;
	}

	/**
	 * Set template name
	 * @param $name
	 * @return Application_Model_Models_Template
	 */
	public function setName($name) {
		$this->_name = $name;
		return $this;
	}

	/**
	 * Returns template content
	 * @return string
	 */
	public function getContent() {
		return $this->_content;
	}

	/**
	 * Set template content
	 * @param $content
	 * @return Application_Model_Models_Template
	 */
	public function setContent($content) {
		$this->_content = $content;
		return $this;
	}

	/**
	 * Returns type of template
	 * @return string
	 */
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
		//$this->_type = $this->_validateType($type);
		$this->_type = $type;
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

	/**
	 * Set old name
	 * Used for template renaming
	 * @param $oldName
	 * @return Application_Model_Models_Template
	 */
	public function setOldName($oldName) {
		$this->_oldName = $oldName;
		return $this;
	}

	/**
	 * Get old name
	 * Used for template renaming
	 * @return null
	 */
	public function getOldName() {
		return $this->_oldName;
	}
}

