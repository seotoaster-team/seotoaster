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

	const OPT_ERRLAND          = 'errland';

	const OPT_MEMLAND          = 'mamland';

	const OPT_SIGNUPLAND       = 'signupland';

	const CONTEXT_NEWS         = 'news';

	protected $_templateId       = '';

	protected $_parentId         = 0;

	protected $_showInMenu       = self::IN_NOMENU;

	protected $_navName          = '';

	protected $_metaDescription  = '';

    protected $_metaKeywords     = '';

    protected $_headerTitle      = '';

    protected $_url              = '';

    protected $_h1               = '';

    protected $_teaserText       = '';

	protected $_lastUpdate       = '';

	protected $_is404page        = false;

	protected $_protected        = false;

	protected $_memLanding        = false;

	protected $_errLoginLanding  = false;

	protected $_signupLanding    = false;

	protected $_order            = 0;

	protected $_targetedKey      = '';

	protected $_siloId           = 0;

	protected $_content          = '';

	protected $_system           = false;

	protected $_draft            = false;

	protected $_news             = false;

	protected $_publishAt      = '';

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

	public function getMemLanding() {
		return $this->_memLanding;
	}

	public function setMemLanding($memLanding) {
		$this->_memLanding = $memLanding;
		return $this;
	}

	public function getErrLoginLanding() {
		return $this->_errLoginLanding;
	}

	public function setErrLoginLanding($errLoginLanding) {
		$this->_errLoginLanding = $errLoginLanding;
		return $this;
	}

	public function getSignupLanding() {
		return $this->_signupLanding;
	}

	public function setSignupLanding($signupLanding) {
		$this->_signupLanding = $signupLanding;
		return $this;
	}

	public function getOrder() {
		return $this->_order;
	}

	public function setOrder($order) {
		$this->_order = $order;
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

	public function getSystem() {
		return $this->_system;
	}

	public function setSystem($system) {
		$this->_system = $system;
		return $this;
	}

	public function getDraft() {
		return $this->_draft;
	}

	public function setDraft($draft) {
		$this->_draft  = $draft;
		if($draft) {
			$this->_system = $draft;
		}
		return $this;
	}

	public function getPublishAt() {
		return $this->_publishAt;
	}

	public function setPublishAt($publishAt) {
		$this->_publishAt = $publishAt;
		return $this;
	}

	public function getNews() {
		return $this->_news;
	}

	public function setNews($news) {
		$this->_news = $news;
		if($news) {
			$this->_system = $news;
		}
		return $this;
	}


}

