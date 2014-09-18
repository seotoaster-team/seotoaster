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


    protected $_magicLabel = false;

    /**
     * Magic space parameters. Available since 2.0.6
     *
     * @var null
     */
    protected $_params       = array();

	public function __construct($name = '', $content = '', $toasterData = array(), $params = array(), $magicLabel = false) {
		$this->_name         = $name;
		$this->_content      = $content;
		$this->_toasterData  = $toasterData;
        $this->_params       = $params;
        $this->_magicLabel   = $magicLabel;
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
        if($this->_magicLabel){
            $space = Tools_Content_Parser::MAGIC_SPACE_LABEL.Tools_Content_Parser::OPTIONS_SEPARATOR.strtolower($this->_name);
        }else{
		    $space = strtolower($this->_name);
        }
        //put parameter back into a string for valid parsing
        $params = (is_array($this->_params) && !empty($this->_params)) ? (':' . implode(':', $this->_params)) : '';
	    preg_match('~{' . $space . $params . '}(.*){/' . $space . '}~suiU', $this->_content, $found);
		return (is_array($found) && !empty($found) && isset($found[1])) ? $found[1] : '';
	}

	protected function _replace($spaceContent) {
        if($this->_magicLabel){
            $space = Tools_Content_Parser::MAGIC_SPACE_LABEL.Tools_Content_Parser::OPTIONS_SEPARATOR.strtolower($this->_name);
        }else{
            $space = strtolower($this->_name);
        }
        //put parameter back for replacement
        $params = (is_array($this->_params) && !empty($this->_params)) ? (':' . implode(':', $this->_params)) : '';
        return preg_replace('~{' . $space . $params . '}.*?{/' . $space . '}~sui', $this->_escapeChars($spaceContent), $this->_content, 1);

	}

    private function _escapeChars($content) {
        $chars = array(
            '$' => '\$'
        );
        return str_replace(array_keys($chars), array_values($chars), $content);
    }
}
