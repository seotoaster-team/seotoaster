<?php

abstract class Tools_MagicSpaces_Abstract {

	protected $_name         = '';

	/**
	 * Full and parsed page content
	 *
	 * @var string
	 */
	protected $_content      = '';

	/**
	 * Parsed magic space content
	 *
	 * @var string
	 */
	protected $_spaceContent = '';

	/**
	 * Seotoaster's current page data
	 *
	 * @var array
	 */
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
	 * If this method return's any content it will be place instead of the original magigspace content
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
		return preg_replace('~{' . $space . '}.*?{/' . $space . '}~sui', $this->_escapeChars($spaceContent), $this->_content, 1);
	}

    private function _escapeChars($content) {
        $chars = array(
            '$' => '\$'
        );
        return str_replace(array_keys($chars), array_values($chars), $content);
    }
}
