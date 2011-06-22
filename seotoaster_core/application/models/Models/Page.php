<?php

class Application_Model_Models_Page extends Application_Model_Models_Abstract implements Zend_Acl_Resource_Interface {

	const IN_MAINMENU          = 1;

	const IN_STATICMENU        = 2;

	const IN_NOMENU            = 0;

	const IDCATEGORY_DEFAULT   = -1;

	const IDCATEGORY_DRAFT     = -2;

	const IDCATEGORY_PRODUCT   = -3;

	const IDCATEGORY_CATEGORY  = 0;

	const PROTECTED_SIGN       = '*';

	const OPT_PROTECTED        = 'protected';

	const OPT_404PAGE          = 'is_404page';

	private $_templateId       = '';

	private $_parentId         = 0;

	private $_showInMenu       = self::IN_NOMENU;

	private $_navName          = '';

	private $_metaDescription  = '';

    private $_metaKeywords     = '';

    private $_headerTitle      = '';

    private $_url              = '';

    private $_h1               = '';

    private $_teaserText       = '';

	private $_lastUpdate       = '';

	private $_is404page        = false;

	private $_protected        = false;

	private $_memLandig        = false;

	private $_order            = 0;

	private $_staticOrder      = 0;

	private $_targetedKey      = '';

	private $_siloId           = 0;

	private $_content          = '';

	private $_system           = false;

	public function getContent() {
		return $this->_content;
	}

	public function setContent($content) {
		$this->_content = $content;
		return $this;
	}


	public function getTemplateId() {
		return $this->_templateId;
	}

	public function setTemplateId($templateId) {
		$this->_templateId = $templateId;
		return $this;
	}

	public function getParentId() {
		return $this->_parentId;
	}

	public function setParentId($parentId) {
		$this->_parentId = $parentId;
		return $this;
	}

	public function getShowInMenu() {
		return $this->_showInMenu;
	}

	public function setShowInMenu($showInMenu) {
		$this->_showInMenu = $showInMenu;
		return $this;
	}

	public function getNavName() {
		return $this->_navName;
	}

	public function setNavName($navName) {
		$this->_navName = $navName;
		return $this;
	}

	public function getMetaDescription() {
		return $this->_metaDescription;
	}

	public function setMetaDescription($metaDescription) {
		$this->_metaDescription = $metaDescription;
		return $this;
	}

	public function getMetaKeywords() {
		return $this->_metaKeywords;
	}

	public function setMetaKeywords($metaKeywords) {
		$this->_metaKeywords = $metaKeywords;
		return $this;
	}

	public function getHeaderTitle() {
		return $this->_headerTitle;
	}

	public function setHeaderTitle($headerTitle) {
		$this->_headerTitle = $headerTitle;
		return $this;
	}

	public function getUrl() {
		return $this->_url;
	}

	public function setUrl($url) {
		$this->_url = $url;
		return $this;
	}

	public function getH1() {
		return $this->_h1;
	}

	public function setH1($h1) {
		$this->_h1 = $h1;
		return $this;
	}

	public function getTeaserText() {
		return $this->_teaserText;
	}

	public function setTeaserText($teaserText) {
		$this->_teaserText = $teaserText;
		return $this;
	}

	public function getLastUpdate() {
		return $this->_lastUpdate;
	}

	public function setLastUpdate($lastUpdate) {
		$this->_lastUpdate = $lastUpdate;
		return $this;
	}

	public function getIs404page() {
		return $this->_is404page;
	}

	public function setIs404page($is404page) {
		$this->_is404page = $is404page;
		$this->_system    = $this->_is404page;
		return $this;
	}

	public function getProtected() {
		return $this->_protected;
	}

	public function setProtected($protected) {
		$this->_protected = $protected;
		return $this;
	}

	public function getMemLandig() {
		return $this->_memLandig;
	}

	public function setMemLandig($memLandig) {
		$this->_memLandig = $memLandig;
		return $this;
	}

	public function getOrder() {
		return $this->_order;
	}

	public function setOrder($order) {
		$this->_order = $order;
		return $this;
	}

	public function getStaticOrder() {
		return $this->_staticOrder;
	}

	public function setStaticOrder($staticOrder) {
		$this->_staticOrder = $staticOrder;
		return $this;
	}

	public function getTargetedKey() {
		return $this->_targetedKey;
	}

	public function setTargetedKey($targetedKey) {
		$this->_targetedKey = $targetedKey;
		return $this;
	}

	public function getSiloId() {
		return $this->_siloId;
	}

	public function setSiloId($siloId) {
		$this->_siloId = $siloId;
		return $this;
	}

	public function isCategory() {
		return ($this->_categoryId == 0);
	}

	public function setId($id) {
		parent::setId($id);
		return $this;
	}

	public function getResourceId() {
		return ($this->_protected) ? Tools_Security_Acl::RESOURCE_PAGE_PROTECTED : Tools_Security_Acl::RESOURCE_PAGE_PUBLIC;
	}

	public function  toArray() {
		$vars = array();
		$methods = get_class_methods($this);
		$props   = get_class_vars(get_class($this));
        foreach ($props as $key => $value) {
			$method = 'get' . ucfirst($this->_normalizeOptionsKey($key));
            if (in_array($method, $methods)) {
                $vars[str_replace('_', '', $key)] = $this->$method();
            }
        }
        return $vars;
	}

	public function getSystem() {
		return $this->_system;
	}

	public function setSystem($system) {
		$this->_system = $system;
		return $this;
	}



}

