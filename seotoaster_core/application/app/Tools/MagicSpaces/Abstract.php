<?php

abstract class Tools_MagicSpaces_Abstract
{
    protected $_name           = '';

    /**
     * Full and parsed page content
     *
     * @var string
     */
    protected $_content        = '';

    /**
     * Parsed magic space content
     *
     * @var string
     */
    protected $_spaceContent   = '';

    /**
     * Seotoaster's current page data
     *
     * @var array
     */
    protected $_toasterData    = array();


    protected $_magicLabel     = false;

    /**
     * Magic space parameters. Available since 2.0.6
     *
     * @var null
     */
    protected $_params         = array();

    /*
     * Contains information for render magicSpace
     *
     * @var string
     */
    protected $_replaceKey     = '';

    /**
     * Parse before widgets
     *
     * @var bool
     */
    protected $_parseBefore    = false;

    /**
     * @var bool
     */
    protected $_recursiveParse = true;

    public function __construct(
        $name = '',
        $content = '',
        $toasterData = array(),
        $params = array(),
        $magicLabel = false
    ) {
        $this->_name         = $name;
        $this->_content      = $content;
        $this->_toasterData  = $toasterData;
        $this->_params       = $params;
        $this->_magicLabel   = $magicLabel;
        $this->_replaceKey   = (is_array($params) && !empty($params)) ? (':' . implode(':', $params)) : '';
        $this->_spaceContent = $this->_parse();
        $this->_init();
    }

    /**
     * This method should be overloaded in the descendant classes
     * If this method return's any content it will be place instead of the original magigspace content
     *
     * @abstract
     */
    abstract protected function _run();

    protected function _init()
    {
    }

    /**
     * @return bool
     */
    public function isAllowedParseBefore()
    {
        return $this->_parseBefore;
    }

    public function isAllowedRecursiveParse()
    {
        return $this->_recursiveParse;
    }

    public function run()
    {
        $content = $this->_run();

        return $this->_replace(($content !== null) ? $content : $this->_spaceContent);
    }

    protected function _parse()
    {
        if (!$this->_name) {
            return '';
        }

        if ($this->_magicLabel) {
            $space = Tools_Content_Parser::MAGIC_SPACE_LABEL.Tools_Content_Parser::OPTIONS_SEPARATOR
                .strtolower($this->_name);
        }
        else {
            $space = strtolower($this->_name);
        }
        // Put parameter back into a string for valid parsing
        preg_match('~{'.$space.$this->_replaceKey.'}(.*){/'.$space.'}~suiU', $this->_content, $found);

        return (is_array($found) && !empty($found) && isset($found[1])) ? $found[1] : '';
    }

    protected function _replace($spaceContent)
    {
        if ($this->_magicLabel) {
            $space = Tools_Content_Parser::MAGIC_SPACE_LABEL.Tools_Content_Parser::OPTIONS_SEPARATOR
                .strtolower($this->_name);
        }
        else {
            $space = strtolower($this->_name);
        }
        // Put parameter back for replacement
        return preg_replace(
            '~{'.$space.$this->_replaceKey.'}.*?{/'.$space.'}~sui',
            $this->_escapeChars($spaceContent),
            $this->_content,
            1
        );

    }

    private function _escapeChars($content)
    {
        $chars = array(
            '$' => '\$'
        );
        
        return str_replace(array_keys($chars), array_values($chars), $content);
    }
}
