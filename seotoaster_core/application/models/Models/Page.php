<?php

class Application_Model_Models_Page extends Application_Model_Models_Abstract implements Zend_Acl_Resource_Interface {

	const IN_MAINMENU          = 1;

	const IN_STATICMENU        = 2;

	const IN_NOMENU            = 0;

	const IDCATEGORY_DEFAULT   = -1;

	const IDCATEGORY_DRAFT     = -2;

	const IDCATEGORY_CATEGORY  = 0;

	const PROTECTED_SIGN       = '*';

	const OPT_PROTECTED        = 'option_protected';

	const OPT_404PAGE          = 'option_404page';

	const OPT_ERRLAND          = 'option_member_loginerror';

	const OPT_MEMLAND          = 'option_member_landing';

	const OPT_SIGNUPLAND       = 'option_member_signuplanding';

    const IS_NEWS_PAGE         = '1';

    const OPTION_USAGE_ONCE    = 'once';

    const OPTION_USAGE_MANY    = 'many';

	protected $_templateId        = '';

	protected $_parentId          = 0;

	protected $_showInMenu        = self::IN_NOMENU;

	protected $_navName           = '';

	protected $_metaDescription   = '';

    protected $_metaKeywords      = '';

    protected $_headerTitle       = '';

    protected $_url               = '';

    protected $_h1                = '';

    protected $_teaserText        = '';

	protected $_lastUpdate        = '';

	protected $_order             = 0;

	protected $_targetedKeyPhrase = '';

	protected $_siloId            = 0;

	protected $_content           = '';

	protected $_system            = false;

	protected $_draft             = false;

	protected $_news              = false;

	protected $_publishAt         = '';

	protected $_optimized         = false;

    protected $_extraOptions      = array();

	protected $_previewImage      = null;

    protected $_containers        = array();

    protected $_externalLinkStatus = 0;

    protected $_externalLink = '';

    protected $_pageType = 1;

    /**
     * @param array $containers
     */
    public function setContainers($containers) {
        $this->_containers = $containers;
    }

    /**
     * @return array
     */
    public function getContainers() {
        return $this->_containers;
    }


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
		return $this->_getExtraOption(self::OPT_404PAGE);
	}

	public function setIs404page($is404page) {
        $is404page = (boolean) $is404page;
		($is404page) ? $this->_setExtraOption(self::OPT_404PAGE) : $this->_unsetExtraOption(self::OPT_404PAGE);
		$this->_system = $is404page;
		return $this;
	}

	public function getProtected() {
        return $this->_getExtraOption(self::OPT_PROTECTED);
	}

	public function setProtected($protected) {
		($protected) ? $this->_setExtraOption(self::OPT_PROTECTED) : $this->_unsetExtraOption(self::OPT_PROTECTED);
		return $this;
	}

	public function getMemLanding() {
		return $this->_getExtraOption(self::OPT_MEMLAND);
	}

	public function setMemLanding($memLanding) {
		($memLanding) ? $this->_setExtraOption(self::OPT_MEMLAND) : $this->_unsetExtraOption(self::OPT_MEMLAND);
		return $this;
	}

	public function getErrLoginLanding() {
		return $this->_getExtraOption(self::OPT_ERRLAND);
	}

	public function setErrLoginLanding($errLoginLanding) {
		($errLoginLanding) ? $this->_setExtraOption(self::OPT_ERRLAND) : $this->_unsetExtraOption(self::OPT_ERRLAND);
		return $this;
	}

	public function getSignupLanding() {
		return $this->_getExtraOption(self::OPT_SIGNUPLAND);
	}

	public function setSignupLanding($signupLanding) {
        ($signupLanding) ? $this->_setExtraOption(self::OPT_SIGNUPLAND) : $this->_unsetExtraOption(self::OPT_SIGNUPLAND);
		return $this;
	}

	public function getOrder() {
		return $this->_order;
	}

	public function setOrder($order) {
		$this->_order = $order;
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
		return (in_array(self::OPT_PROTECTED, $this->_extraOptions)) ? Tools_Security_Acl::RESOURCE_PAGE_PROTECTED : Tools_Security_Acl::RESOURCE_PAGE_PUBLIC;
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
		$this->_system = $draft;
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

	public function setOptimized($optimized) {
		$this->_optimized = (boolean)$optimized;
		return $this;
	}

	public function getOptimized() {
		return $this->_optimized;
	}

    public function setExternalLink($externalLink)
    {
        $this->_externalLink = $externalLink;
        return $this;
    }

    public function getExternalLink()
    {
        return $this->_externalLink;
    }

    public function setExternalLinkStatus($externalLinkStatus)
    {
        $this->_externalLinkStatus = $externalLinkStatus;
        return $this;
    }

    public function getExternalLinkStatus()
    {
        return $this->_externalLinkStatus;
    }

    /**
     * Set an extra options for the page
     *
     * Pass array as extra options and false for the $force param and new options will be merged with the current ones
     * Pass array as extra options and true for the $force param and current extra options will be replaced with the new ones
     * Pass false as extra options and extra options for the current page will be removed
     *
     * @param array|string|boolean $extraOptions
     * @param bool $force Replace or not current extra options
     * @return Application_Model_Models_Page
     */
    public function setExtraOptions($extraOptions, $force = false) {
        if(is_array($extraOptions)) {
            $this->_extraOptions = (!$force) ? array_merge($extraOptions, $this->_extraOptions) : $extraOptions;
        } else if ((boolean)$extraOptions === false) {
            $this->_extraOptions = array();
        } else {
            if(!in_array($extraOptions, $this->_extraOptions)) {
                array_push($this->_extraOptions, $extraOptions);
            }
        }
        return $this;
    }

    public function getExtraOptions() {
        return $this->_extraOptions;
    }

    public function getExtraOption($option) {
        return $this->_getExtraOption($option);
    }

    protected function _getExtraOption($option) {
        return in_array($option, $this->_extraOptions);
    }

    protected function _setExtraOption($option) {
        if(!in_array($option, $this->_extraOptions)) {
            array_push($this->_extraOptions, $option);
        }
    }

    protected function _unsetExtraOption($option) {
        unset($this->_extraOptions[array_search($option, $this->_extraOptions)]);
    }

    public function setTargetedKeyPhrase($targetedKeyPhrase) {
        $this->_targetedKeyPhrase = $targetedKeyPhrase;
        return $this;
    }

    public function getTargetedKeyPhrase() {
        return $this->_targetedKeyPhrase;
    }

	public function setPreviewImage($previewImage) {
		$this->_previewImage = $previewImage;
		return $this;
	}

	public function getPreviewImage() {
		return $this->_previewImage;
	}

    /**
     * @return int
     */
    public function getPageType()
    {
        return $this->_pageType;
    }

    /**
     * @param int $pageType
     * @return int
     */
    public function setPageType($pageType)
    {
        $this->_pageType = $pageType;

        return $this;
    }



}