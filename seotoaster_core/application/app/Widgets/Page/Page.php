<?php
/**
 * $page widget
 *
 * @author iamne
 */
class Widgets_Page_Page extends Widgets_Abstract {

    /**
     * page preview source
     */
    const PAGE_PREVIEW_SRC = 'src';

    /**
     * Original page value (not optimized)
     */
    const PAGE_ORIGINAL = 'original';

    /**
     * Clear HTML Tags
     */
    const CLEAR_TAGS = 'clear';

    const DEFAULT_THUMB_SIZE  = 250;

    const DEFAULT_THUMB_SIZE_HEIGHT  = 166;

    private $_aliases = array(
        'title' => 'headerTitle',
        'teaser' => 'teaserText',
        'navname' => 'navName'
    );

	protected function _init() {
		array_push($this->_cacheTags , 'pageid_'.$this->_toasterOptions['id']);
	}

	protected function  _load() {
        $option = $this->_validateOption($this->_options[0]);
	    if(isset($this->_toasterOptions[$option])) {
            $original = (isset($this->_options[1]) && $this->_options[1] == self::PAGE_ORIGINAL);
            $optionMakerName = 'get' . ucfirst($option);

            if($original) {
                $page = Application_Model_Mappers_PageMapper::getInstance()->find($this->_toasterOptions['id'], $original);
                $optionValue = $page->$optionMakerName();
            }elseif (in_array('external', $this->_options)){
                $page = Application_Model_Mappers_PageMapper::getInstance()->find($this->_toasterOptions['id'], $original);
                if(!$page->getExternalLinkStatus()){
                    return  $page->$optionMakerName();
                }
                $optionValue = $page->getExternalLink();
            }
            else {
                $optionValue = $this->_toasterOptions[$option];
            }

            if(in_array(self::CLEAR_TAGS, $this->_options, true)) {
                $optionValue = strip_tags($optionValue);
            }

            $optionMakerNameLocal = '_'.$optionMakerName;
            if (method_exists($this, $optionMakerNameLocal)) {
                return $this->$optionMakerNameLocal($optionValue);
            }
            return $optionValue;
        } else {
            $optionMakerName = '_generate' . ucfirst($this->_options[0]) . 'Option';
            if(method_exists($this, $optionMakerName)) {
                return $this->$optionMakerName();
            }
        }
		return 'Wrong widget option: <strong>' . $this->_options[0] . '</strong>';
	}

    private function _validateOption($option) {
        return array_key_exists($option, $this->_aliases) ? $this->_aliases[$option] : $option;
    }

    /**
     * Get page teaser text
     *
     * @param string $optionValue value of page option
     * @return string processed value of page option
     */
    private function _getTeaserText($optionValue)
    {
        if (isset($this->_options[2]) && is_numeric($this->_options[2])) {
            $optionValue = Tools_Text_Tools::cutText($optionValue, $this->_options[2]);
        }

        return $optionValue;
    }

    private function _getUrl($url)
    {
        if ($this->_toasterOptions['pageFolder']) {
            if (empty($this->_toasterOptions['isFolderIndex'])) {
                $url = $this->_toasterOptions['pageFolder'] . '/' . $url;
            } else {
                $url = $this->_toasterOptions['pageFolder'] . '/';
            }
        }

        if(in_array('clearindex', $this->_options)) {
            $url = ($url === Helpers_Action_Website::DEFAULT_PAGE) ? '' : $url;
        }

        return $url;
    }

	private function _generateIdOption() {
		return $this->_toasterOptions['id'];
	}


	private function _generateTeaserOption() {
		return $this->_toasterOptions['teaserText'];
	}

    private function _generateCategoryOption()
    {
        if (isset($this->_options[1])) {
            $content = '';
            $pageMapper = Application_Model_Mappers_PageMapper::getInstance();
            $registry = Zend_Registry::getInstance();
            if ($registry->isRegistered('pageForCategory')) {
                $page = $registry->get('pageForCategory');
            } else {
                $page = $pageMapper->find($this->_toasterOptions['id']);
                $registry->set('pageForCategory', $page);
            }
            if (!$page instanceof Application_Model_Models_Page) {
                if (Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_CONTENT)) {
                    throw new Exceptions_SeotoasterWidgetException('Cant load page!');
                }
            }
            if ($page->getParentId() > 0) {
                if ($registry->isRegistered('pageCategory')) {
                    $page = $registry->get('pageCategory');
                } else {
                    $page = $pageMapper->find($page->getParentId());
                    $registry->set('pageCategory', $page);
                }
                if (!$page instanceof Application_Model_Models_Page) {
                    if (Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_CONTENT)) {
                        throw new Exceptions_SeotoasterWidgetException('Cant load category page!');
                    }
                }
                switch ($this->_options[1]) {
                    case 'name':
                        $content = $page->getNavName();
                        break;
                    case 'link':
                        $content = $page->getUrl();
                        break;
                    default:
                        break;
                }
            }
            return $content;
        }
        return '';

    }

	private function _generatePreviewOption() {
        $configData = Application_Model_Mappers_ConfigMapper::getInstance()->getConfig();
		$websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('Website');
        $confiHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('Config');
		$pageHelper    = Zend_Controller_Action_HelperBroker::getStaticHelper('Page');
 		$files         = Tools_Filesystem_Tools::findFilesByExtension($websiteHelper->getPath() . $websiteHelper->getPreview(), '(jpg|gif|png|jpeg|webp)', false, false, false);
		$pagePreviews  = array_values(preg_grep('~^' . $pageHelper->clean(preg_replace('~/+~', '-', $this->_toasterOptions['url'])) . '\.(png|jpg|gif|jpeg|webp)$~', $files));
		$fileInfo = array();

        $websiteUrlMediaServer = ($confiHelper->getConfig('mediaServers') ? Tools_Content_Tools::applyMediaServers($websiteHelper->getUrl()) : $websiteHelper->getUrl());

		if(!empty($pagePreviews)) {
            $path = (isset($this->_options) && end($this->_options) == 'crop') ? $websiteHelper->getPreviewCrop()
                : $websiteHelper->getPreview();
            $src =  $websiteUrlMediaServer.$path.$pagePreviews[0].'?'.strtotime('now');
            $imagePath =  $websiteHelper->getPath().$path.$pagePreviews[0];
            if (!empty($imagePath)) {
                $fileInfo = getimagesize($imagePath);
            }

            $loadingLazy = '';
            if(in_array('lazy', $this->_options)) {
                $loadingLazy = 'loading="lazy"';
            }

            if(in_array('crop', $this->_options) && !empty($configData['cropNewFormat'])) {
                $cropOptionKey = array_search('crop', $this->_options);
                $cropOptionParams = $this->_options[$cropOptionKey +1];

                $cropSizeSubfolder = '';
                $cropParams = array();

                $newWidth = self::DEFAULT_THUMB_SIZE;
                $newHeight = 'auto';

                if(!empty($cropOptionParams)) {
                    preg_match('/^([0-9]+)x?([0-9]*)/i', $cropOptionParams, $cropParams);

                    if (isset($cropParams[1], $cropParams[2]) && is_numeric($cropParams[1]) && $cropParams[2] == '') {
                        $cropParams[2] = $cropParams[1];
                    }
                    unset($cropParams[0]);

                    if (!empty($cropParams)) {
                        $cropSizeSubfolder = implode($cropParams, '-').DIRECTORY_SEPARATOR;

                        $newWidth = $cropParams[1];
                        $newHeight = $cropParams[2];
                    }
                }

                // Create a folder crop-size subfolder
                if(!empty($cropSizeSubfolder)) {
                    $pathPreview   = $websiteHelper->getPath().$websiteHelper->getPreviewCrop().$cropSizeSubfolder;
                    if (!is_dir($pathPreview)) {
                        Tools_Filesystem_Tools::mkDir($pathPreview);
                    }
                }

                $cropStatus = Tools_Image_Tools::resizeByParameters(
                    $imagePath,
                    $newWidth,
                    $newHeight,
                    true,
                    $websiteHelper->getPath() . $websiteHelper->getPreviewCrop() . $cropSizeSubfolder,
                    true
                );

                if($cropStatus) {
                    $src = $websiteUrlMediaServer . $websiteHelper->getPreviewCrop() . $cropSizeSubfolder . $pagePreviews[0].'?'.strtotime('now');

                    if($newHeight == 'auto') {
                        $newHeight = self::DEFAULT_THUMB_SIZE_HEIGHT;
                    }

                    return '<img '. $loadingLazy .' class="page-teaser-image" src="'.$src.'" alt="'.$pageHelper->clean($this->_toasterOptions['h1']).'" width="'. $newWidth .'" height="'. $newHeight .'" />';
                }
            } else if(isset($this->_options[1]) && $this->_options[1] === self::PAGE_PREVIEW_SRC) {
                return $src;
            }
			return '<img '. $loadingLazy .' class="page-teaser-image" src="'.$src.'" alt="'.$pageHelper->clean($this->_toasterOptions['h1']).'" '.(isset($fileInfo[3]) ? $fileInfo[3] : "").' />';
		}
		return;
	}

	public static function getAllowedOptions() {
		$translator = Zend_Registry::get('Zend_Translate');
		return array(
			array(
				'alias'   => $translator->translate('Current page id'),
				'option' => 'page:id'
			),
            array(
                'alias'   => $translator->translate('Current page url'),
                'option' => 'page:url'
            ),
			array(
				'alias'   => $translator->translate('Current page h1'),
				'option' => 'page:h1'
			),
			array(
				'alias'   => $translator->translate('Current page title'),
				'option' => 'page:title'
			),
			array(
				'alias'   => $translator->translate('Current page teaser image'),
				'option' => 'page:preview'
			),
            array(
                'alias'   => $translator->translate('Current page teaser image cropped'),
                'option' => 'page:preview:crop'
            ),
			array(
				'alias'   => $translator->translate('Current page teaser text'),
				'option' => 'page:teaser'
			),
            array(
                'alias'   => $translator->translate('Current page navigation name'),
                'option' => 'page:navname'
            )
		);
	}

}

