<?php
abstract class Tools_MagicSpaces_Abstract {

	protected $_name         = '';

	protected $_content      = '';

	protected $_spaceContent = '';

	protected $_toasterData  = array();

	public function __construct($name = '', $content = '', $toasterData = array()) {
		$this->_name         = $name;
		$this->_content      = $content;
		$this->_toasterData  = $toasterData;
		$this->_spaceContent = $this->_parse();
		$this->_init();
	}

	/**
	 * This method should be overloaded in the descendant classes
	 * If this method return's any con
	 * @abstract
	 *
	 */
	abstract protected function _run();

	protected function _init() {}

	public function run() {
		$content = $this->_run();
		return $this->_replace(($content !== null) ? $content : $this->_spaceContent);
	}

	protected function _parse() {
		if(!$this->_name) {
			return '';
		}
		$space = strtolower($this->_name);
 		preg_match('~{' . $space . '}(.*){/' . $space . '}~suiU', $this->_content, $found);
		return (is_array($found) && !empty($found) && isset($found[1])) ? $found[1] : '';
	}

	protected function _replace($spaceContent) {
		$space = strtolower($this->_name);
		return preg_replace('~{' . $space . '}(.*){/' . $space . '}~suiU', $spaceContent, $this->_content, 1);
	}
}
