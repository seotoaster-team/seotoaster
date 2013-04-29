<?php
/**
 * Seotoaster themes API
 *
 * @author Eugene I. Nezhuta <theneiam@gmail.com>
 * Date: 12/4/12
 * Time: 5:23 PM
 */
class Api_Toaster_Themes extends Api_Service_Abstract {

    const THEME_SQL_FILE           = 'theme.sql';

	const THEME_DATA_FILE          = 'theme.json';

    const THEME_MEDIA_DIR          = 'media';

    const THEME_PAGE_TEASERS_DIR   = 'previews';

    const THEME_KIND_LIGHT         = 'light';

    const THEME_KIND_FULL          = 'full';

    const THEME_FULL_MAX_PAGES     = 10;

	protected $_websiteHelper      = null;

    protected $_themesConfig       = array();

    protected $_configHelper       = null;

    protected $_cacheHelper        = null;

    protected $_protectedTemplates = array('index', 'default', 'category');

    protected $_translator         = null;

    /**
     * Queries to execute during the full theme download.
     *
     * Data will be dumped for the 'page', 'container', 'featured_area', 'page_option', 'page_fa', 'page_has_option' tables
     * @var array
     */
    protected $_fullThemesSqlMap   = array(
//        'page'            => 'SELECT * FROM `page`',
        'container'       => 'SELECT * FROM `container` WHERE page_id IN (?) ;',
        'featured_area'   => 'SELECT * FROM `featured_area`;',
        'page_fa'         => 'SELECT * FROM `page_fa` WHERE page_id IN (?) ;',
        'page_option'     => 'SELECT * FROM `page_option`;',
        'page_has_option' => 'SELECT * FROM `page_has_option` WHERE page_id IN (?) ;'
    );

    /**
     * API access list.
     *
     * Allows full access for the superadmin and admin
     * Allows download and upload for the user role
     * @var array
     */
    protected $_accessList         = array(
        Tools_Security_Acl::ROLE_SUPERADMIN => array('allow' => array('get', 'post', 'put', 'delete')),
        Tools_Security_Acl::ROLE_ADMIN      => array('allow' => array('get', 'post', 'put', 'delete')),
        Tools_Security_Acl::ROLE_USER       => array('allow' => array('get', 'put'))
    );

    public function init() {
        $this->_websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
        $this->_configHelper  = Zend_Controller_Action_HelperBroker::getStaticHelper('config');
        $this->_cacheHelper   = Zend_Controller_Action_HelperBroker::getStaticHelper('cache');
        $this->_themesConfig  = Zend_Registry::get('theme');
        $this->_translator    = Zend_Registry::get('Zend_Translate');
    }

    /**
     * Get a list of themes or start theme download
     *
     * Supported params:
     * 1. name - Theme name. Tells API that this is an attempt to download a theme
     *     E.g. http://seotoaster.dev/api/toaster/themes/name/ecommerce
     * 2. kind [full|light] - Type of theme to download. Requires name param
     *     E.g. http://seotoaster.dev/api/toaster/themes/name/ecommerce/kind/full
     * 3. sql [1|0] - Tells API to include or not sql dump to the full theme. Requires name and kind=full
     *     E.g. http://seotoaster.dev/api/toaster/themes/name/ecommerce/kind/full/sql/0
     * 4. media [1|0] - Tells API to include or not media directory to the full theme. Requires name and kind=full
     *     E.g. http://seotoaster.dev/api/toaster/themes/name/ecommerce/kind/full/media/0
     * 5. teasers [1|0] - Tells API to include or not previews directory to the full theme. Requires name and kind=full
     *     E.g. http://seotoaster.dev/api/toaster/themes/name/ecommerce/kind/full/teasers/0
     * Full example
     *     http://seotoaster.dev/api/toaster/themes/name/ecommerce/kind/full/sql/0/media/1/teasers/1
     * @return array
     */
    public function getAction() {
        $themesPath = $this->_websiteHelper->getPath() . $this->_themesConfig['path'];

        // if parameter 'name' specified in the query, we assume user is trying to download a theme
        if($this->_request->has('name')) {
            $themeName = filter_var($this->_request->getParam('name'), FILTER_SANITIZE_STRING);
            $themePath = $themesPath . $themeName;

            // check if full theme requested - perform necessary actions
            $isFull    = $this->_request->has('kind') && $this->_request->getParam('kind') == self::THEME_KIND_FULL;

	        // exporting theme
	        $this->_exportTheme($themeName, $isFull);
        }
        // themes list request
        $themesList = array();
        $themesDirs = Tools_Filesystem_Tools::scanDirectoryForDirs($themesPath);

        // there are no themes in the theme directory
        if(empty($themesDirs)) {
            $this->_error($this->_translator->translate('Aw! No themes found!'), self::REST_STATUS_NOT_FOUND);
        }
        foreach ($themesDirs as $themeName) {
            $files         = Tools_Filesystem_Tools::scanDirectory($themesPath . $themeName, false, false);
            $requiredFiles = preg_grep('/^(' . implode('|', $this->_protectedTemplates) . ')\.html$/i', $files);
            if(sizeof($requiredFiles) != sizeof($this->_protectedTemplates)) {
                continue;
            }
            $previews = preg_grep('/^preview\.(png|jpg|gif)$/i', $files);
	        $hasData = file_exists($themesPath.$themeName.DIRECTORY_SEPARATOR.self::THEME_DATA_FILE) ||
			        is_dir($themesPath.$themeName.DIRECTORY_SEPARATOR.'media/') ||
			        is_dir($themesPath.$themeName.DIRECTORY_SEPARATOR.'previews/');
            array_push($themesList, array(
                'name'      => $themeName,
                'preview'   => !empty ($previews) ? $this->_websiteHelper->getUrl() . $this->_themesConfig['path'] . $themeName . '/' . reset($previews) : $this->_websiteHelper->getUrl() . 'system/images/noimage.png',
                'isCurrent' => ($this->_configHelper->getConfig('currentTheme') == $themeName),
	            'hasData'   => $hasData
            ));
        }
        if(empty($themesList)) {
            $this->_error($this->_translator->translate('Aw! Looks like none of your themes are valid!'), self::REST_STATUS_NOT_FOUND);
        }
        return $themesList;
    }

    /*
     * Apply theme
     *
     */
    public function putAction() {
        //backup current theme TURNED OFF FOR NOW
//        $this->_exportTheme();

        $themeName        = filter_var($this->_request->getParam('name'), FILTER_SANITIZE_STRING);
	    $data             = Zend_Json::decode($this->_request->getRawBody());
        $themePath        = $this->_websiteHelper->getPath() . $this->_themesConfig['path'] . $themeName;
        if (is_dir($themePath)) {
            // save templates in the database with proper type from theme.ini
            $this->_applyTemplates($themeName);

	        // proccess theme.sql + import media folder
            if(isset($data['applyData']) && $data['applyData'] === true){
	            // @todo backup current theme

	            if (file_exists($themePath . DIRECTORY_SEPARATOR . self::THEME_DATA_FILE)) {
                    $this->_applySql($themeName);
	            }

                //applying media content
	            $themeMediaPath       = $themePath . DIRECTORY_SEPARATOR . self::THEME_MEDIA_DIR;
	            $themePageTeasersPath = $themePath . DIRECTORY_SEPARATOR . self::THEME_PAGE_TEASERS_DIR;

	            if (is_dir($themeMediaPath)){
		            Tools_Filesystem_Tools::copy($themeMediaPath, $this->_websiteHelper->getPath() . $this->_websiteHelper->getMedia());
		            //@todo Process images with Tools_Image_Tools::batchResize
	            }
	            if (is_dir($themePageTeasersPath)){
		            //processing preview images if any
		            Tools_Filesystem_Tools::copy($themePageTeasersPath, $this->_websiteHelper->getPath() . $this->_websiteHelper->getPreview());
	            }
            }

            $this->_cacheHelper->clean(false, false);
        }
    }

	/**
	 * Delete theme
	 * @return bool
	 */
	public function deleteAction() {
        $themeName = filter_var($this->_request->getParam('name'), FILTER_SANITIZE_STRING);
        if($this->_configHelper->getConfig('currentTheme') == $themeName) {
            $this->_error('Current theme cannot be removed!', self::REST_STATUS_FORBIDDEN);
        }
        return Tools_Filesystem_Tools::deleteDir($this->_websiteHelper->getPath() . $this->_themesConfig['path'] . $themeName);
    }


    private function _applyTemplates($themeName, $remove = false) {
        $themePath   = $this->_websiteHelper->getPath() . $this->_themesConfig['path'] . $themeName;
        $themeFiles  = Tools_Filesystem_Tools::findFilesByExtension($themePath, '(html|htm)', true, true, false);
        $themeConfig = false;
        $errors      = array();

        //check we are not missing any required template
        foreach($this->_protectedTemplates as $template){
            if (!array_key_exists($template, $themeFiles)){
                array_push($errors, $this->_translator->translate('Theme missing %s\$1 template', $template));
            }
        }

        if(!empty($errors)) {
            $this->_error(join('<br />', $errors), self::REST_STATUS_BAD_REQUEST);
        }

        //trying to get theme.ini file with templates presets
        try {
            $themeConfig = parse_ini_string(Tools_Filesystem_Tools::getFile($themePath . '/' . Tools_Template_Tools::THEME_CONFIGURATION_FILE));
        } catch (Exception $e) {
            $themeConfig = false;
        }

        $mapper = Application_Model_Mappers_TemplateMapper::getInstance();
        $mapper->clearTemplates(); // this will remove all templates except system required. @see $_protectedTemplates
        foreach($themeFiles as $templateName => $templateFile) {
            $template = $mapper->find($templateName);
            if (!$template instanceof Application_Model_Models_Template) {
                $template = new Application_Model_Models_Template();
                $template->setName($templateName);
            }
            //no matter add or edit -> we are setting the type if we can
            if(is_array($themeConfig) && !empty($themeConfig)) {
                if(array_key_exists($templateName, $themeConfig)) {
                    $template->setType($themeConfig[$templateName]);
                }
            }

            // getting template content
            try{
                $template->setContent(Tools_Filesystem_Tools::getFile($templateFile));
            } catch (Exceptions_SeotoasterException $e){
                array_push($errors, 'Can\'t read template file: ' . $templateName);
            }

            // saving template to db
            $mapper->save($template);
            unset($template);
        }

        //updating config table
        Application_Model_Mappers_ConfigMapper::getInstance()->save(array('currentTheme' => $themeName));
        if(!empty($errors)) {
            $this->_error(join('<br />', $errors, self::REST_STATUS_BAD_REQUEST));
        }
        return true;
    }

    private function _applySql($themeName) {
        try {
//            $themeSql = Tools_Filesystem_Tools::getFile($this->_websiteHelper->getPath() . $this->_themesConfig['path'] . $themeName . '/' . self::THEME_SQL_FILE);
	        $dataJson = new Zend_Config_Json($this->_websiteHelper->getPath() . $this->_themesConfig['path'] . $themeName . '/' . self::THEME_DATA_FILE);
        } catch(Exception $e) {
            error_log($e->getMessage());
            return false;
        }
//        if(!strlen($themeSql)) {
//            return false;
//        }
//        $queries = Tools_System_SqlSplitter::split($themeSql);
//        if(!is_array($queries) || empty($queries)) {
//            return false;
//        }

	    try {
		    /**
		     * @var $dbAdapter Zend_Db_Adapter_Abstract
		     */
		    $dbAdapter = Zend_Registry::get('dbAdapter');
	        $dbAdapter->beginTransaction();
            $dbAdapter->query('SET foreign_key_checks = 0;');

            // cleaning needed tables before apply a full theme
//            array_walk(array_keys($this->_fullThemesSqlMap), function($table) use($dbAdapter) {
//                $dbAdapter->query('DELETE FROM `' .$table. '`;');
//            });

	        $themeData = $dataJson->toArray();
            //clean optimize table
	        if (array_key_exists('page', $themeData)){
                $dbAdapter->query('DELETE FROM `optimized`;');
	        }

	        if (!empty($themeData)) {
		        foreach ($themeData as $table => $data) {
			        if (empty($data)){
				        continue;
			        } else {
				        $dbAdapter->delete($table);
			        }
			        foreach ($data as $row){
				        $dbAdapter->insert($table, $row);
			        }
		        }
	        }

            $dbAdapter->query('SET foreign_key_checks = 1;');
		    $dbAdapter->commit();
        }
        catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public function postAction() {}

    protected function _exportData($themeName) {
        $themePath   = $this->_websiteHelper->getPath() . $this->_themesConfig['path'] . $themeName;

        // init empty array for export data
	    $data         = array();

	    /**
	     * @var $dbAdapter Zend_Db_Adapter_Abstract
	     */
	    $dbAdapter = Zend_Registry::get('dbAdapter');

	    // fetching index page and main menu pages
	    $pagesSqlWhere = "SELECT * FROM `page` WHERE system = '0' AND draft = '0' AND (
		url = 'index.html'
		OR (parent_id = '0' AND show_in_menu = '1') OR (parent_id = '-1' AND show_in_menu = '2')
	    OR (parent_id = '0' OR parent_id IN (SELECT DISTINCT `page`.`id` FROM `page` WHERE (parent_id = '0') AND (system = '0') AND (show_in_menu = '1')) )
	    OR id IN ( SELECT DISTINCT `page`.`id` FROM `page_has_option` )
	    )
	    ORDER BY `order` ASC";

	    $pages  = $dbAdapter->fetchAll($pagesSqlWhere);
	    // @todo: inject from plugins here ???

	    // getting list of pages ids for export
	    $pagesIDs = array_map(function($page){
		    return $page['id'];
	    }, $pages);

	    // dumping pages first
	    $data['page'] = $pages;

	    foreach ($this->_fullThemesSqlMap as $table => $query){
		    $data[$table] = $dbAdapter->fetchAll($dbAdapter->quoteInto($query, $pagesIDs));
	    }

	    // @todo: add plugin hooks to export content

//	    $sql = array();
//	    array_walk($data, function($data, $table) use (&$sql) {
//		    $sql[] = Tools_Theme_Tools::dump($table, $data);
//	    });
//	    $sql = implode(PHP_EOL, array_filter($sql));

	    try {
//		    Tools_Filesystem_Tools::saveFile($themePath . DIRECTORY_SEPARATOR . self::THEME_SQL_FILE, $sql);
	    } catch (Exceptions_SeotoasterException $se) {
		    error_log($se->getMessage());
	    }

	    return $data;
    }

    protected function _applyMedia($pathesMap, $createSource = false, $move = false) {
        if(!is_array($pathesMap) || empty($pathesMap)) {
            return;
        }
        $errors = array();
        foreach($pathesMap as $themeMediaDir => $toasterMediaDir) {
            if(!is_dir($themeMediaDir)) {
                if($createSource) {
                    Tools_Filesystem_Tools::mkDir($$themeMediaDir);
                }
                continue;
            }
            try {
                Tools_Filesystem_Tools::copy($themeMediaDir, $toasterMediaDir, array('.git', '.gitignore'));
            } catch (Exceptions_SeotoasterException $se) {
                $errors[] = $se->getMessage();
            }
        }
        return (!empty($errors)) ? $errors : true;
    }

	/**
	 * Method exports zipped theme
	 * @param string $themeName Theme name
	 * @param bool   $full      Set true to dump data and media files
	 */
	protected function _exportTheme($themeName = '', $full = false) {
        if(!$themeName) {
            $themeName = $this->_configHelper->getConfig('currentTheme');
        }
        $themePath = $this->_websiteHelper->getPath() . $this->_themesConfig['path'] . $themeName .DIRECTORY_SEPARATOR;

		if ($full) {
	        //exporting sql for the full theme
			$data = $this->_exportData($themeName);

			if (!empty($data)){
				$exportData = new Zend_Config($data);
				$themeDataFile = new Zend_Config_Writer_Json(array(
					'config'    => $exportData,
					'filename'  => $themePath . self::THEME_DATA_FILE
				));
				$themeDataFile->write();
			}

			// exporting media files
			$mediaFiles = $this->_exportMedia($data['page'], $data['container']);
		}

		if ($themeName === $this->_configHelper->getConfig('currentTheme')) {
			// saving template types into theme.ini. @see Tools_Template_Tools::THEME_CONFIGURATION_FILE
			$themeIniConfig = new Zend_Config(array(), true);
			foreach (Application_Model_Mappers_TemplateMapper::getInstance()->fetchAll() as $template) {
				$themeIniConfig->{$template->getName()} = $template->getType();
			}
			if (!empty($themeIniConfig)){
				try {
					$iniWriter = new Zend_Config_Writer_Ini(array(
						'config' => $themeIniConfig,
						'filename'   => $themePath . Tools_Template_Tools::THEME_CONFIGURATION_FILE
					));
					$iniWriter->write();
				} catch (Exception $e) {
					error_log($e->getMessage());
				}
			}
			unset($themeIniConfig, $iniWriter);
		}

		$excludeFiles = array();
		if (!$full) {
			array_push($excludeFiles, 'theme.sql');
			array_push($excludeFiles, self::THEME_DATA_FILE);
		}

		$themeArchive = Tools_Theme_Tools::zip($themeName, (isset($mediaFiles) ? $mediaFiles : false), $excludeFiles);

//		$body = file_get_contents($themeArchive);

//		if ($body){
//			Tools_Filesystem_Tools::deleteFile($themeArchive);
//		}

		$this->_response->clearAllHeaders();//->clearBody();
        $this->_response->setHeader('Content-Disposition', 'attachment; filename=' . $themeName . '.zip')
            ->setHeader('Content-Type', 'application/zip', true)
            ->setHeader('Content-Transfer-Encoding', 'binary', true)
            ->setHeader('Expires', date(DATE_RFC1123), true)
            ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)
            ->setHeader('Pragma', 'public', true)
            ->setBody(file_get_contents($themeArchive))
            ->sendResponse();
        exit;
    }

	protected function _exportMedia($pages, $containers = false) {
		if (empty($pages)){
			throw new Exceptions_SeotoasterException('Given empty pages to export media procedure');
		}
		$media = array();

		$previewFolder = $this->_websiteHelper->getPreview();

		$pagePreviews = array_filter(array_map(function($page) use ($previewFolder) {
			return !empty($page['preview_image']) ? $previewFolder . $page['preview_image'] : false ;
		}, $pages));

		$contentImages = array();
		if ($containers) {
			foreach ($containers as $container) {
				preg_match_all('~src="(media[^">]+)"~iu', $container['content'], $matches);
				if (!empty($matches[1])){
					$contentImages = array_merge($contentImages, $matches[1]);
				}
				unset($matches, $container);
			}
		}

		return array_merge($pagePreviews, $contentImages);
	}
}
