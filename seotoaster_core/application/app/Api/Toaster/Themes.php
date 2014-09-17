<?php
/**
 * Seotoaster themes API
 *
 * @author Eugene I. Nezhuta <theneiam@gmail.com>
 *         Date: 12/4/12
 *         Time: 5:23 PM
 */
class Api_Toaster_Themes extends Api_Service_Abstract {

	/**
	 * @deprecated Using json file instead
	 */
	const THEME_SQL_FILE = 'theme.sql';

	const THEME_DATA_FILE = 'theme.json';

	const THEME_MEDIA_DIR = 'media';

	const THEME_PAGE_TEASERS_DIR = 'previews';

	const THEME_KIND_LIGHT = 'light';

	const THEME_KIND_FULL = 'full';

	const THEME_FULL_MAX_PAGES = 10;

	const THEME_FULL_MAX_FILESIZE = 31457280;

	const PLUGIN_EXPORT_METHOD = 'exportWebsiteData';

	protected $_websiteHelper = null;

	protected $_themesConfig = array();

	protected $_configHelper = null;

	protected $_cacheHelper = null;

	protected $_translator = null;

	/**
	 * Queries to execute during the full theme download.
	 *
	 * Data will be dumped for the 'page', 'container', 'featured_area', 'page_option', 'page_fa', 'page_has_option' tables
	 * @var array
	 */
	protected $_fullThemesSqlMap = array(
//        'page'            => 'SELECT * FROM `page`',
		'container'       => 'SELECT * FROM `container` WHERE page_id IS NULL OR page_id IN (?) ;',
		'featured_area'   => 'SELECT * FROM `featured_area`;',
		'page_fa'         => 'SELECT * FROM `page_fa` WHERE page_id IN (?) ;',
		'page_option'     => 'SELECT * FROM `page_option`;',
		'page_has_option' => 'SELECT * FROM `page_has_option` WHERE page_id IN (?) ;',
		'form'            => 'SELECT * FROM `form`;',
		'template_type'   => 'SELECT * FROM `template_type`;',
        'plugin_newslog_news'  => 'SELECT * FROM `plugin_newslog_news`;'
	);

	/**
	 * API access list.
	 *
	 * Allows full access for the superadmin and admin
	 * Allows download and upload for the user role
	 * @var array
	 */
	protected $_accessList = array(
		Tools_Security_Acl::ROLE_SUPERADMIN => array('allow' => array('get', 'post', 'put', 'delete')),
		Tools_Security_Acl::ROLE_ADMIN      => array('allow' => array('get', 'post', 'put', 'delete')),
		Tools_Security_Acl::ROLE_USER       => array('allow' => array('get', 'put'))
	);

	public function init() {
		$this->_websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
		$this->_configHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('config');
		$this->_cacheHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('cache');
		$this->_themesConfig = Zend_Registry::get('theme');
		$this->_translator = Zend_Registry::get('Zend_Translate');
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

        if($this->_request->getParam('exportBackup', false)){
            // exporting theme
            $this->_exportTheme(null, true, true);
            return array('responseText' => $this->_translator->translate('Backup theme created!'));
        }
        if($this->_request->getParam('importBackup', false)){
            $themeName = $this->_configHelper->getConfig('currentTheme');

            // exporting theme
            $this->_applySql($themeName);
            $this->_applyMedia($themeName);
            $this->_cacheHelper->clean(false, false);
            return array('responseText' => $this->_translator->translate('Backup theme uploaded!'));
        }

		$themesPath = $this->_websiteHelper->getPath() . $this->_themesConfig['path'];

		// if parameter 'name' specified in the query, we assume user is trying to download a theme
		if ($this->_request->has('name')) {
			$themeName = filter_var($this->_request->getParam('name'), FILTER_SANITIZE_STRING);
			$themePath = $themesPath . $themeName;

			// check if full theme requested - perform necessary actions
			$isFull = $this->_request->has('kind') && $this->_request->getParam('kind') == self::THEME_KIND_FULL;

			// exporting theme
			$this->_exportTheme($themeName, $isFull);
		}
		// themes list request
		$themesList = array();
		$themesDirs = Tools_Filesystem_Tools::scanDirectoryForDirs($themesPath);

		// there are no themes in the theme directory
		if (empty($themesDirs)) {
			$this->_error($this->_translator->translate('Aw! No themes found!'), self::REST_STATUS_NOT_FOUND);
		}

        $protectedTemplates = Tools_Theme_Tools::$protectedTemplates;
		foreach ($themesDirs as $themeName) {
			$files = Tools_Filesystem_Tools::scanDirectory($themesPath . $themeName, false, false);
			$requiredFiles = preg_grep('/^(' . implode('|', $protectedTemplates) . ')\.html$/i', $files);
			if (sizeof($requiredFiles) != sizeof($protectedTemplates)) {
				continue;
			}
			$previews = preg_grep('/^preview\.(png|jpg|gif)$/i', $files);
			$hasData = file_exists($themesPath . $themeName . DIRECTORY_SEPARATOR . self::THEME_DATA_FILE) ||
					is_dir($themesPath . $themeName . DIRECTORY_SEPARATOR . 'media/') ||
					is_dir($themesPath . $themeName . DIRECTORY_SEPARATOR . 'previews/');
			array_push($themesList, array(
				'name'      => $themeName,
				'preview'   => !empty ($previews) ? $this->_websiteHelper->getUrl() . $this->_themesConfig['path'] . $themeName . '/' . reset($previews) : $this->_websiteHelper->getUrl() . 'system/images/noimage.png',
				'isCurrent' => ($this->_configHelper->getConfig('currentTheme') == $themeName),
				'hasData'   => $hasData
			));
		}
		if (empty($themesList)) {
			$this->_error($this->_translator->translate('Aw! Looks like none of your themes are valid!'), self::REST_STATUS_NOT_FOUND);
		}
		return $themesList;
	}

	/*
	 * Apply theme
	 *
	 */
	public function putAction() {
		$themeName = filter_var($this->_request->getParam('name'), FILTER_SANITIZE_STRING);
		$data = Zend_Json::decode($this->_request->getRawBody());
		$themePath = $this->_websiteHelper->getPath() . $this->_themesConfig['path'] . $themeName;
		if (is_dir($themePath)) {
			// save templates in the database with proper type from theme.ini
            try {
                Tools_Theme_Tools::applyTemplates($themeName);
            }
            catch (Exception $e) {
                $this->_error($e->getMessage());
            }

			// process theme.sql + import media folder
			if (isset($data['applyData']) && $data['applyData'] === true) {
				// backup current theme
				$this->_exportTheme(null, true, true);

				if (file_exists($themePath . DIRECTORY_SEPARATOR . self::THEME_DATA_FILE)) {
			        $this->_applySql($themeName);
				}

				// applying media content
				$themeMediaPath = $themePath . DIRECTORY_SEPARATOR . $this->_websiteHelper->getMedia();
				$themePageTeasersPath = $themePath . DIRECTORY_SEPARATOR . $this->_websiteHelper->getPreviews();

				if (is_dir($themeMediaPath) || is_dir($themePageTeasersPath)) {
					$this->_applyMedia($themeName);
				}
			}

			$this->_cacheHelper->clean(false, false);
		}
	}

	protected function _applyMedia($themeName = false) {
		if (!$themeName) {
			$themeName = $this->_configHelper->getConfig('currentTheme');
		}

		$toasterRoot = $this->_websiteHelper->getPath();
		$themePath = $toasterRoot . $this->_themesConfig['path'] . $themeName;

		$themeMediaPath = $themePath . DIRECTORY_SEPARATOR . $this->_websiteHelper->getMedia();
		$themePageTeasersPath = $themePath . DIRECTORY_SEPARATOR . $this->_websiteHelper->getPreview();

		//processing images from media folder
		if (is_dir($themeMediaPath)) {
			$mediaFiles = glob($themeMediaPath . join(DIRECTORY_SEPARATOR, array('*', '*.{jpeg,jpg,png,gif}')), GLOB_BRACE);
			$toasterMedia = $toasterRoot . $this->_websiteHelper->getMedia();
			foreach ($mediaFiles as $originalFile) {
				$filepath = str_replace($themeMediaPath, '', $originalFile);
				$filepath = explode(DIRECTORY_SEPARATOR, $filepath);
				if (!is_array($filepath)) {
					continue;
				}
				list($folderName, $fileName) = $filepath;
				$destFolderPath = $toasterMedia . $folderName;
				if (!is_dir($destFolderPath)) {
					if (Tools_Filesystem_Tools::mkDir($destFolderPath)) {
						Tools_Filesystem_Tools::mkDir($destFolderPath . DIRECTORY_SEPARATOR . 'original');
					}
				}

				$destImgPath = $destFolderPath . DIRECTORY_SEPARATOR . 'original' . DIRECTORY_SEPARATOR . $fileName;
				if (Tools_Filesystem_Tools::copy($originalFile, $destImgPath, true)) {
					Tools_Image_Tools::batchResize($destImgPath, $destFolderPath);
				}

				unset($filepath, $destFolderPath, $folderName, $fileName);
			}
		}

		//processing page preview images
		if (is_dir($themePageTeasersPath)) {
			$destinationPreview = $toasterRoot . $this->_websiteHelper->getPreview();
			if (!is_dir($destinationPreview)) {
				Tools_Filesystem_Tools::mkDir($destinationPreview);
			}
			Tools_Filesystem_Tools::copy($themePageTeasersPath, $destinationPreview, array(), true);
		}

	}

	/**
	 * Delete theme
	 * @return bool
	 */
	public function deleteAction() {
		$themeName = filter_var($this->_request->getParam('name'), FILTER_SANITIZE_STRING);
		if ($this->_configHelper->getConfig('currentTheme') == $themeName) {
			$this->_error('Current theme cannot be removed!', self::REST_STATUS_FORBIDDEN);
		}
		return Tools_Filesystem_Tools::deleteDir($this->_websiteHelper->getPath() . $this->_themesConfig['path'] . $themeName);
	}

	private function _applySql($themeName) {
		try {
			$dataJson = new Zend_Config_Json($this->_websiteHelper->getPath() . $this->_themesConfig['path'] . $themeName . '/' . self::THEME_DATA_FILE);
		} catch (Exception $e) {
			error_log($e->getMessage());
			return false;
		}

		try {
			/**
			 * @var $dbAdapter Zend_Db_Adapter_Abstract
			 */
			$dbAdapter = Zend_Registry::get('dbAdapter');
			$dbAdapter->beginTransaction();
			$dbAdapter->query('SET foreign_key_checks = 0;');

			$themeData = $dataJson->toArray();
			//clean optimize table
			if (array_key_exists('page', $themeData)) {
				$dbAdapter->query('DELETE FROM `optimized`;');
			}

			if (!empty($themeData)) {
                $enabledPlugins = Tools_Plugins_Tools::getEnabledPlugins(true);
                $isShoppingEnable = array_search('shopping',$enabledPlugins);
                $isNewslogEnable  = array_search('newslog',$enabledPlugins);
                foreach ($themeData as $table => $data) {
					if (empty($data)) {
						continue;
					} else {
                        if($table == 'template_type'){
                            if(!$isNewslogEnable){
                                $where = $dbAdapter->quoteInto('id IN (?)', array('type_news', 'type_news_list'));
                                $dbAdapter->delete($table, $where);
                            } else if(!$isShoppingEnable) {
                                $where = $dbAdapter->quoteInto('id IN (?)', array('typecheckout', 'typeproduct', 'typelisting'));
                                $dbAdapter->delete($table, $where);
                            }
                        } else {
                            $dbAdapter->delete($table);
                        }
					}
					foreach ($data as $row) {
						try {
							$dbAdapter->insert($table, $row);
						} catch (Exception $exc) {
							Tools_System_Tools::debugMode() && error_log($exc->getMessage());
							continue;
						}
					}
				}
			}

			$dbAdapter->query('SET foreign_key_checks = 1;');
			$dbAdapter->commit();
		} catch (Exception $e) {
			Tools_System_Tools::debugMode() && error_log($e->getMessage());
			$dbAdapter->rollBack();
			return false;
		}
	}

	public function postAction() {}

	/**
	 * Method exports zipped theme
	 * @param string $themeName Theme name
	 * @param bool   $full      Set true to dump data and media files
	 */
	protected function _exportTheme($themeName = '', $full = false, $noZip = false) {
		if (!$themeName) {
			$themeName = $this->_configHelper->getConfig('currentTheme');
		}
		$themePath = $this->_websiteHelper->getPath() . $this->_themesConfig['path'] . $themeName . DIRECTORY_SEPARATOR;
		$websitePath = $this->_websiteHelper->getPath();

		if ($full) {
            $exportData = $this->_exportSqlToJson($themePath);

			// exporting list of media files
			$totalFileSize = 0; // initializing files size counter

			$previewFolder = $this->_websiteHelper->getPreview();
			$pagePreviews = array_filter(array_map(function ($page) use ($previewFolder) {
				return !empty($page['preview_image']) ? $previewFolder . $page['preview_image'] : false;
			}, $exportData['data']['page']));

			$contentImages = array(); // list of images from containers
			if (!empty($data['container'])) {
				foreach ($data['container'] as $container) {
					preg_match_all('~media[^"\']*\.(?:jpe?g|gif|png)~iu', $container['content'], $matches);
					if (!empty($matches[0])) {
						$contentImages = array_merge($contentImages, array_map(function ($file) {
							$file = explode(DIRECTORY_SEPARATOR, $file);
							if ($file[2] !== 'original') {
								$file[2] = 'original';
							}
							return implode(DIRECTORY_SEPARATOR, $file);
						}, $matches[0]));
					}
					unset($matches, $container);
				}
			}

			$mediaFiles = array_merge($pagePreviews, $contentImages, $exportData['mediaFiles']);
			$mediaFiles = array_unique(array_filter($mediaFiles));

			if (!empty($mediaFiles)) {
				clearstatcache();
				foreach ($mediaFiles as $key => $file) {
					if (!is_file($websitePath . $file)) {
						$mediaFiles[$key] = null;
						continue;
					}
					$totalFileSize += filesize($websitePath . $file);
				}
			}
			if ($totalFileSize > self::THEME_FULL_MAX_FILESIZE) {
				$this->_error('Too many images');
			} else {
				$mediaFiles = array_filter($mediaFiles);
			}
		}

		// if requested name is current one we create system file with template types
		if ($themeName === $this->_configHelper->getConfig('currentTheme')) {
			// saving template types into theme.ini. @see Tools_Template_Tools::THEME_CONFIGURATION_FILE
			$themeIniConfig = new Zend_Config(array(), true);
			foreach (Application_Model_Mappers_TemplateMapper::getInstance()->fetchAll() as $template) {
				$themeIniConfig->{$template->getName()} = $template->getType();
			}
			if (!empty($themeIniConfig)) {
				try {
					$iniWriter = new Zend_Config_Writer_Ini(array(
						'config'   => $themeIniConfig,
						'filename' => $themePath . Tools_Template_Tools::THEME_CONFIGURATION_FILE
					));
					$iniWriter->write();
				} catch (Exception $e) {
					Tools_System_Tools::debugMode() && error_log($e->getMessage());
				}
			}
			unset($themeIniConfig, $iniWriter);
		}

		//defining list files that needs to be excluded
		$excludeFiles = array();
		if (!$full) {
			array_push($excludeFiles, self::THEME_DATA_FILE);
		}

		if ($noZip === true) {
			// backup media files to theme subfolder
			if (!empty($mediaFiles)) {
				if (!is_dir($themePath . 'previews')) {
					Tools_Filesystem_Tools::mkDir($themePath . 'previews');
				}
				if (!is_dir($themePath . 'media')) {
					Tools_Filesystem_Tools::mkDir($themePath . 'media');
				}
				foreach ($mediaFiles as $file) {
					if (!is_file($websitePath . $file)) {
						continue;
					}
					$path = explode(DIRECTORY_SEPARATOR, $file);
					if (!is_array($path) || empty($path)) {
						continue;
					}
					switch ($path[0]) {
						case 'previews':

							list ($folder, $filename) = $path;
							break;
						case 'media':
							$folder = 'media' . DIRECTORY_SEPARATOR . $path[1];
							if (!is_dir($themePath . $folder)) {
								Tools_Filesystem_Tools::mkDir($themePath . $folder);
							}
							$filename = end($path);
							break;
						default:
							continue;
							break;
					}
					$destination = $themePath . $folder . DIRECTORY_SEPARATOR . $filename;
					try {
						$r = Tools_Filesystem_Tools::copy($websitePath . $file, $destination);
					} catch (Exception $e) {
						Tools_System_Tools::debugMode() && error_log($e->getMessage());
					}
				}
			}
			return true;
		} else {
			//create theme zip archive
			$themeArchive = Tools_Theme_Tools::zip($themeName, (isset($mediaFiles) ? $mediaFiles : false), $excludeFiles);

			if ($themeArchive) {
				$body = file_get_contents($themeArchive);
				if (false !== $body) {
					Tools_Filesystem_Tools::deleteFile($themeArchive);
				} else {
					$this->_error('Unable to read website archive file');
				}
			} else {
				$this->_error('Can\'t create website archive');
			}


			//outputting theme zip
			$this->_response->clearAllHeaders()->clearBody();
			$this->_response->setHeader('Content-Disposition', 'attachment; filename=' . $themeName . '.zip')
					->setHeader('Content-Type', 'application/zip', true)
					->setHeader('Content-Transfer-Encoding', 'binary', true)
					->setHeader('Expires', date(DATE_RFC1123), true)
					->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)
					->setHeader('Pragma', 'public', true)
					->setBody($body)
					->sendResponse();
			exit;
		}
	}

	protected function _exportMedia($pages, $containers = false) {
		if (empty($pages)) {
			throw new Exceptions_SeotoasterException('Given empty pages to export media procedure');
		}

		$media = array();
		$totalFileSize = 0;

		$previewFolder = $this->_websiteHelper->getPreview();

		$pagePreviews = array_filter(array_map(function ($page) use ($previewFolder) {
			return !empty($page['preview_image']) ? $previewFolder . $page['preview_image'] : false;
		}, $pages));

		$contentImages = array();
		if ($containers) {
			foreach ($containers as $container) {
				preg_match_all('~media[^"\']*\.(?:jpe?g|gif|png)~iu', $container['content'], $matches);
				if (!empty($matches[0])) {
					$contentImages = array_merge($contentImages, array_map(function ($file) {
						$file = explode(DIRECTORY_SEPARATOR, $file);
						if ($file[2] !== 'original') {
							$file[2] = 'original';
						}
						return implode(DIRECTORY_SEPARATOR, $file);
					}, $matches[0]));
				}
				unset($matches, $container);
			}
		}

		$media = array_merge($pagePreviews, $contentImages);

		if (!empty($media)) {
			$websitePath = $this->_websiteHelper->getPath();
			clearstatcache();
			foreach ($media as $key => $file) {
				if (!is_file($websitePath . $file)) {
					$media[$key] = null;
					continue;
				}
				$totalFileSize += filesize($websitePath . $file);
			}
		}
		if ($totalFileSize > self::THEME_FULL_MAX_FILESIZE) {
			throw new Exceptions_SeotoasterException('Too many images');
		}

		return array_filter(array_unique($media));
	}

    protected function _exportSqlToJson($themePath) {

        /**
         * @var $dbAdapter Zend_Db_Adapter_Abstract
         */
        $dbAdapter = Zend_Registry::get('dbAdapter');

        // exporting themes data for the full theme
        // init empty array for export data
        $data = array(
            'page' => array()
        );
        // and for media files
        $mediaFiles = array();

        // fetching index page and main menu pages and news pages
        $pagesSqlWhere = "SELECT * FROM `page` WHERE system = '0' AND draft = '0' AND (
        url = 'index.html' OR (parent_id = '0' AND show_in_menu = '1') OR (parent_id = '-1' AND show_in_menu = '2')
        OR (parent_id = '0' OR parent_id IN (SELECT DISTINCT `page`.`id` FROM `page` WHERE (parent_id = '0') AND (system = '0') AND (show_in_menu = '1')) )
        OR id IN ( SELECT DISTINCT `page_id` FROM `page_fa` )
        OR id IN ( SELECT DISTINCT `page_id` FROM `page_has_option` )
        ) ORDER BY `order` ASC";

        $pages = $dbAdapter->fetchAll($pagesSqlWhere);
        if (is_array($pages) && !empty($pages)) {
            $data['page'] = $pages;
            unset($pages);
        }

        // combining list of queries for export others tables content
        $queryList = array();

        $enabledPlugins = Tools_Plugins_Tools::getEnabledPlugins(true);
        foreach ($enabledPlugins as $plugin) {
            $pluginsData = Tools_Plugins_Tools::runStatic(self::PLUGIN_EXPORT_METHOD, $plugin);
            if (!$pluginsData) {
                continue;
            }
            if (isset($pluginsData['pages']) && is_array($pluginsData['pages']) && !empty($pluginsData['pages'])) {
                $data['page'] = array_merge($data['page'], $pluginsData['pages']);
            }
            if (isset($pluginsData['tables']) && is_array($pluginsData['tables']) && !empty($pluginsData['tables'])) {
                foreach ($pluginsData['tables'] as $table => $query) {
                    if (array_key_exists($table, $this->_fullThemesSqlMap)) {
                        continue;
                    }
                    $queryList[$table] = $query;
                    unset($table, $query);
                }
            }
            if (isset($pluginsData['media']) && is_array($pluginsData['media']) && !empty($pluginsData['media'])) {
                $mediaFiles = array_unique(array_merge($mediaFiles, $pluginsData['media']));
            }
        }
        unset($enabledPlugins);

        // getting list of pages ids for export
        $pagesIDs = array_map(function ($page) {
                return $page['id'];
            }, $data['page']);

        // building list of dump queries and executing it with page IDS substitution
        $queryList = array_merge($this->_fullThemesSqlMap, $queryList);
        foreach ($queryList as $table => $query) {
            $data[$table] = $dbAdapter->fetchAll($dbAdapter->quoteInto($query, $pagesIDs));
        }
        unset($queryList, $pagesIDs);

        if (!empty($data) && is_dir($themePath)) {
            $exportData = new Zend_Config($data);
            $themeDataFile = new Zend_Config_Writer_Json(array(
                'config'   => $exportData,
                'filename' => $themePath . self::THEME_DATA_FILE
            ));
            $themeDataFile->write();
        }
        return array('data' => $data, 'mediaFiles' => $mediaFiles);
    }
}
