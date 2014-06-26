<?php

/**
 * Tools
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Tools_Page_Tools
{

	const PLACEHOLDER_NOIMAGE = 'system/images/noimage.png';

    public static function getPreview($page, $crop = false)
    {
	    $websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
        $configHelper  = Zend_Controller_Action_HelperBroker::getStaticHelper('config');
	    $path          = (bool)$crop ? $websiteHelper->getPreviewCrop() : $websiteHelper->getPreview() ;

	    if (is_numeric($page)){
			$page = Application_Model_Mappers_PageMapper::getInstance()->find(intval($page));
        }

        if ($page instanceof Application_Model_Models_Page){
	        $validator = new Zend_Validate_Regex('~^https?://.*~');
	        $preview = $page->getPreviewImage();
	        if (!is_null($preview)) {
		        if ($validator->isValid($preview)){
			        return $preview;
		        } else {
			        $websiteUrl = ($configHelper->getConfig('mediaServers') ? Tools_Content_Tools::applyMediaServers($websiteHelper->getUrl()) : $websiteHelper->getUrl());
			        $previewPath = $websiteHelper->getPath().$path.$preview;

			        if (is_file($previewPath)) {
				        return $websiteUrl . $path . $preview;
			        }
		        }
	        }
        }

	    return $websiteHelper->getUrl() . self::PLACEHOLDER_NOIMAGE;
    }

    /**
     * Returns information about preview page, checks crop preview
     * @param        $pageId
     * @param bool   $croped
     * @param string $cropSizeSubfolder
     *
     * @return array
     */
    public static function getPreviewFilePath($pageId, $croped = false, $cropSizeSubfolder = '')
    {
        $websiteHelper     = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
        $cropSizeSubfolder = ($croped && !empty($cropSizeSubfolder)) ? trim($cropSizeSubfolder, '/').'/' : '';
        $infoPreview       = array(
            'sitePath'        => $websiteHelper->getPath(),
            'previewPath'     => $websiteHelper->getPreview(),
            'previewCropPath' => $websiteHelper->getPreviewCrop(),
            'sizeSubfolder'   => $cropSizeSubfolder,
            'fileName'        => '',
            'path'            => '',
            'fullPath'        => ''
        );
        $pathPreview = ($croped) ? $websiteHelper->getPreviewCrop() : $websiteHelper->getPreview();
        if ($croped && $cropSizeSubfolder != '') {
            $pathPreview .= $cropSizeSubfolder;
        }
        $page = Application_Model_Mappers_PageMapper::getInstance()->find($pageId);
        if ($page instanceof Application_Model_Models_Page) {
            $infoPreview['fileName'] = $page->getPreviewImage();
            $fullPath                = $websiteHelper->getPath().$pathPreview.$page->getPreviewImage();
            if (is_file($fullPath) && is_readable($fullPath)) {
                $infoPreview['path']     = $pathPreview.$page->getPreviewImage();
                $infoPreview['fullPath'] = $fullPath;
            }
        }

        return $infoPreview;
    }

	/**
	 * @deprecated Use Tools_Page_Tools::getPreview() instead. Will be removed in 2.2
	 */
	public static function getPreviewPath($pageId, $capIfNoPreview = false, $croped = false)
    {
		Tools_System_Tools::debugMode() && error_log('Called deprecated Tools_Page_Tools::getPreviewPath(). Use Tools_Page_Tools::getPreview() instead');
		$websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
		$configHelper  = Zend_Controller_Action_HelperBroker::getStaticHelper('config');
		$pageHelper    = Zend_Controller_Action_HelperBroker::getStaticHelper('page');
        $websiteUrl    = ($configHelper->getConfig('mediaServers') ? Tools_Content_Tools::applyMediaServers($websiteHelper->getUrl()) : $websiteHelper->getUrl());
		try {
            $previews      = Tools_Filesystem_Tools::findFilesByExtension($websiteHelper->getPath() . (($croped) ? $websiteHelper->getPreviewCrop() :$websiteHelper->getPreview()), 'jpg|png|jpeg|gif', true, true, false);
        } catch (Exceptions_SeotoasterException $se) {
            if(APPLICATION_ENV == 'development') {
                error_log("(Cant find preview thumbnail because: " . $se->getMessage() . "\n" . $se->getTraceAsString());
            }
            return $websiteUrl . 'system/images/noimage.png';
        }

		$page = Application_Model_Mappers_PageMapper::getInstance()->find($pageId);
		if ($page instanceof Application_Model_Models_Page) {
			$cleanUrl = $pageHelper->clean(preg_replace('~/+~', '-', $page->getUrl()));
			unset($page);
			$path = (array_key_exists($cleanUrl, $previews)) ? str_replace($websiteHelper->getPath(), $websiteUrl, $previews[$cleanUrl]) : '';
			if(!$path && $capIfNoPreview) {
				return $websiteUrl . 'system/images/noimage.png';
			}
			return str_replace(DIRECTORY_SEPARATOR, '/', $path);
		}
		return $websiteUrl . 'system/images/noimage.png';
	}

	public static function getDraftPages()
    {
		$cacheHelper    = Zend_Controller_Action_HelperBroker::getStaticHelper('cache');
		if(null === ($draftPages = $cacheHelper->load(Helpers_Action_Cache::KEY_DRAFT, Helpers_Action_Cache::PREFIX_DRAFT))) {
			$draftPages = Application_Model_Mappers_PageMapper::getInstance()->fetchAllDraftPages();
			$cacheHelper->save(Helpers_Action_Cache::KEY_DRAFT, $draftPages, Helpers_Action_Cache::PREFIX_DRAFT, array(Helpers_Action_Cache::TAG_DRAFT), Helpers_Action_Cache::CACHE_LONG);
		}
		return $draftPages;
	}

    public static function getDraftPagesCount()
    {
        $cacheHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('cache');

        if (null === ($draftPagesCount = $cacheHelper->load(Helpers_Action_Cache::KEY_DRAFT_COUNT, Helpers_Action_Cache::PREFIX_DRAFT))) {
            $draftPagesCount = Application_Model_Mappers_PageMapper::getInstance()->getDraftPagesCount();
            $cacheHelper->save(Helpers_Action_Cache::KEY_DRAFT_COUNT, $draftPagesCount, Helpers_Action_Cache::PREFIX_DRAFT, array(Helpers_Action_Cache::TAG_DRAFT), Helpers_Action_Cache::CACHE_LONG);
        }

        return $draftPagesCount;
    }

	public static function getLandingPage($type)
    {
		if(!isset($type) || empty ($type)) {
			throw new Exceptions_SeotoasterException('You should specify landing page type');
		}
		$landingPage = null;
		switch ($type) {
			case Application_Model_Models_Page::OPT_SIGNUPLAND:
				$landingPage = Application_Model_Mappers_PageMapper::getInstance()->findSignupLandign();
			break;
			case Application_Model_Models_Page::OPT_MEMLAND:
				$landingPage = Application_Model_Mappers_PageMapper::getInstance()->findMemberLanding();
			break;
			case Application_Model_Models_Page::OPT_ERRLAND:
				$landingPage = Application_Model_Mappers_PageMapper::getInstance()->findErrorLoginLanding();
			break;
		}
		return $landingPage;
	}

    /**
     * @todo Should me moved to the shopping plugin
     * @static
     * @return null
     */
    public static function getProductCategoryPage()
    {
		// We need to know product category page url
		// This url specified in the bundle plugin "Shopping"
		// But this plugin may not be present in the system (not recommended)
		$shopping = Tools_Plugins_Tools::findPluginByName('shopping');
		$pageUrl  = ($shopping->getStatus() == Application_Model_Models_Plugin::ENABLED) ? Shopping::PRODUCT_CATEGORY_URL : null;
		if($pageUrl === null) {
			return null;
		}
		return Application_Model_Mappers_PageMapper::getInstance()->findByUrl($pageUrl);
	}

	public static function getPagesCountByTemplate($templateName)
    {
		$pageDbTable   = new Application_Model_DbTable_Page();
		return $pageDbTable->getAdapter()->query($pageDbTable->select()->where('template_id="' . $templateName . '"'))->rowCount();
	}

    public static function getPageOptions($activeOnly = false)
    {
        $prepared = array();
        $options  = Application_Model_Mappers_PageOptionMapper::getInstance()->fetchOptions($activeOnly);
        if(!empty($options)) {
            foreach($options as $option) {
                if(isset($option['context']) && $option['context']) {
                    $prepared[$option['context']][$option['id']] = $option['title'];
                } else {
                    $prepared['Common'][$option['id']] = $option['title'];
                }
            }
        }
        return $prepared;
    }

    public static function processPagePreviewImage($pageUrl, $tmpPreviewFile = null)
    {
        $websiteHelper      = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
        $pageHelper         = Zend_Controller_Action_HelperBroker::getStaticHelper('page');
        $websiteConfig      = Zend_Registry::get('website');
        $pageUrl            = str_replace(DIRECTORY_SEPARATOR, '-', $pageHelper->clean($pageUrl));
        $previewPath        = $websiteHelper->getPath() . $websiteHelper->getPreview();

//        $filelist           = Tools_Filesystem_Tools::findFilesByExtension($previewPath, '(jpg|gif|png)', false, false, false);
        $currentPreviewList = glob($previewPath.$pageUrl.'.{jpg,jpeg,png,gif}', GLOB_BRACE);

        if ($tmpPreviewFile) {
            $tmpPreviewFile = str_replace($websiteHelper->getUrl(), $websiteHelper->getPath(), $tmpPreviewFile);
            if (is_file($tmpPreviewFile) && is_readable($tmpPreviewFile)){
                preg_match('/\.[\w]{2,6}$/', $tmpPreviewFile, $extension);
                $newPreviewImageFile = $previewPath . $pageUrl . $extension[0];

                //cleaning form existing page previews
                if(!empty($currentPreviewList)) {
                    foreach ($currentPreviewList as $key => $file) {
                        if(file_exists($file)) {
                            if (Tools_Filesystem_Tools::deleteFile($file)){
//                                unset($currentPreviewList[$key]);
                            }
                        }
                    }
                }

                if (is_writable($newPreviewImageFile)){
                    $status = @rename($tmpPreviewFile, $newPreviewImageFile);
                } else {
                    $status = @copy($tmpPreviewFile, $newPreviewImageFile);
                }
                if ($status && file_exists($tmpPreviewFile)) {
                    Tools_Filesystem_Tools::deleteFile($tmpPreviewFile);
                }

                $miscConfig = Zend_Registry::get('misc');

                //check for the previews crop folder and try to create it if not exists
                $cropPreviewDirPath = $websiteHelper->getPath() . $websiteHelper->getPreviewCrop();
                if(!is_dir($cropPreviewDirPath)) {
                    @mkdir($cropPreviewDirPath);
                } else {
                    // unlink old croped page previews
                    if(!empty($currentPreviewList)) {
                        foreach($currentPreviewList as $fileToUnlink) {
                            $unlinkPath = str_replace($previewPath, $cropPreviewDirPath, $fileToUnlink);

                            if(file_exists($unlinkPath)) {
                                unlink($unlinkPath);
                            }
                        }
                    }
                }
                Tools_Image_Tools::resize($newPreviewImageFile, $miscConfig['pageTeaserCropSize'], false, $cropPreviewDirPath, true);
                unset($miscConfig);

                return $pageUrl . $extension[0];
//                return $websiteHelper->getUrl() . $websiteConfig['preview'] . $pageUrl . $extension[0];
            }
        }

        if (sizeof($currentPreviewList) == 0) {
            return false;
        } else {
            $pagePreviewImage = str_replace($websiteHelper->getPath(), $websiteHelper->getUrl(), reset($currentPreviewList));
        }

        return $pagePreviewImage;
    }

}

