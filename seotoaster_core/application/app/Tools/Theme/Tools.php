<?php
/**
 * Theme
 *
 * @author Eugene I. Nezhuta <theneiam@gmail.com>
 *         Date: 12/18/12
 *         Time: 2:14 AM
 */
class Tools_Theme_Tools {

    const FOLDER_CSS             = 'css';

	public static $requiredFiles = array(
		'index.html',
		'default.html',
		'category.html',
		'style.css',
		'content.css'
	);

    public static $protectedTemplates = array(
        'index',
        'default',
        'category'
    );

	public static function dump($table, $query) {
		$dbAdapter = Zend_Registry::get('dbAdapter');
		$result = is_string($query) ? $dbAdapter->fetchAll($query) : $query;
		$sqlDump = '';
		if (is_array($result)) {
			$length = sizeof($result);
			foreach ($result as $key => $data) {
				if (!$sqlDump) {
					$sqlDump .= 'INSERT INTO `' . $table . '` (' . join(', ', array_map(function ($key) {
								return '`' . $key . '`';
							}, array_keys($data))) . ') VALUES ';
				}
				$sqlDump .= '(' . join(', ', array_map(function ($value) use ($dbAdapter) {
							return $dbAdapter->quote($value);
						}, array_values($data))) . (($key == ($length - 1)) ? (');' . PHP_EOL) : '),');
			}
		}
		return $sqlDump;
	}

	public static function zip($themeName, $addFiles = false, $exclude = null) {
		$websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
		$configHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('config');
		$themesConfig = Zend_Registry::get('theme');

		$zip = new ZipArchive();
		$destinationFile = $websiteHelper->getPath() . $websiteHelper->getTmp() . $themeName . '.zip';

		$themePath = $websiteHelper->getPath() . $themesConfig['path'] . $themeName;

		if (true === ($zip->open($destinationFile, ZIPARCHIVE::CREATE))) {
			$themeFiles = Tools_Filesystem_Tools::scanDirectory($themePath, true, true);

			foreach ($themeFiles as $file) {
				if (is_array($exclude) && in_array(Tools_Filesystem_Tools::basename($file), $exclude)) {
					continue;
				}
				$localName = str_replace($themePath, '', $file);
				$localName = trim($localName, DIRECTORY_SEPARATOR);
				$zip->addFile($file, $localName);
				unset($localName);
			}

			if (!empty($addFiles)) {
				foreach ($addFiles as $file) {
					$file = urldecode($file);
					$realPath = $websiteHelper->getPath() . $file;
					if (!file_exists($realPath)) {
						continue;
					} elseif (is_array($exclude) && in_array(Tools_Filesystem_Tools::basename($file), $exclude)) {
						continue;
					}
					$pathParts = explode(DIRECTORY_SEPARATOR, $file);
					if ($pathParts[0] === 'media' && sizeof($pathParts) === 4) {
						// removing original folder level from zip
						unset ($pathParts[2]);
						$zip->addFile($websiteHelper->getPath() . $file, implode(DIRECTORY_SEPARATOR, $pathParts));
					} else {
						// assume that this is a preview file
						$zip->addFile($realPath, $file);
					}

					unset($realPath, $pathParts);
				}
			}

			$zip->close();
			return $destinationFile;
		} else {
			throw new Exceptions_SeotoasterException('Unable to write ' . $destinationFile);
		}
	}

    public static function urlContentCss() {
        $websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
        $configHelper  = Zend_Controller_Action_HelperBroker::getStaticHelper('config');
        $themesConfig  = Zend_Registry::get('theme');
        $themesPath    = $themesConfig['path'].$configHelper->getConfig('currentTheme').DIRECTORY_SEPARATOR;
        $filePath      = $websiteHelper->getPath().$themesPath.self::FOLDER_CSS.DIRECTORY_SEPARATOR.'content.css';
        $folderCssPath = (is_file($filePath)) ? self::FOLDER_CSS.DIRECTORY_SEPARATOR : '';

        return $websiteHelper->getUrl().$themesPath.$folderCssPath.'content.css';
    }

    public static function urlResetCss() {
        $websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
        $configHelper  = Zend_Controller_Action_HelperBroker::getStaticHelper('config');
        $themesConfig  = Zend_Registry::get('theme');
        $themesPath    = $themesConfig['path'].$configHelper->getConfig('currentTheme').DIRECTORY_SEPARATOR;
        $filePath      = $websiteHelper->getPath().$themesPath.self::FOLDER_CSS.DIRECTORY_SEPARATOR.'reset.css';
        $folderCssPath = (is_file($filePath)) ? self::FOLDER_CSS.DIRECTORY_SEPARATOR : '';

        return $websiteHelper->getUrl().$themesPath.$folderCssPath.'reset.css';
    }

    public static function applyTemplates($themeName) {
        $websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
        $themesConfig  = Zend_Registry::get('theme');
        $themePath     = $websiteHelper->getPath().$themesConfig['path'].$themeName.DIRECTORY_SEPARATOR;
        $themeFiles    = glob($themePath. '{,mobile' . DIRECTORY_SEPARATOR . '}*.html', GLOB_BRACE);

        if ($themeFiles !== false) {
            $themeFiles = array_map(function ($file) use ($themePath) {
                return str_replace($themePath, '', $file);
            }, $themeFiles);
        }

        $errors         = array();
        $languageHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('language');
        // Check we are not missing any required template
        foreach (self::$protectedTemplates as $template) {
            if (!in_array($template.'.html', $themeFiles)) {
                array_push($errors, $languageHelper->translate('Theme missing template: ') . $template);
            }
        }

        if (!empty($errors)) {
            throw new Exception(join('<br />', $errors));
        }

        // This will remove all templates except system required. @see $protectedTemplates
        Application_Model_Mappers_TemplateMapper::getInstance()->clearTemplates();

        self::addTemplates($themePath, $themeFiles);

        // Updating config table
        Application_Model_Mappers_ConfigMapper::getInstance()->save(array('currentTheme' => $themeName));

        return true;
    }

    public static function addTemplates($themePath, $filesName = array()) {
        $themePath         = rtrim($themePath, DIRECTORY_SEPARATOR);
        $errors            = array();
        $themeConfig       = self::getThemeIniData($themePath);
        $templateMapper    = Application_Model_Mappers_TemplateMapper::getInstance();
        $templateTypeTable = new Application_Model_DbTable_TemplateType();

        foreach ($filesName as $templateFile) {
            $templateName = preg_replace(
                array('/\\'.DIRECTORY_SEPARATOR.'/', '/\.html/'),
                array('_', ''),
                $templateFile
            );
            $template     = $templateMapper->find($templateName);
            if (!$template instanceof Application_Model_Models_Template) {
                $template = new Application_Model_Models_Template();
                $template->setName($templateName);
            }

            // Checking if we have template type in theme.ini or page meet mobile template naming convention
            if (is_array($themeConfig) && !empty($themeConfig) && array_key_exists($templateName, $themeConfig)) {
                $templateType = $themeConfig[$templateName];
            }
            elseif (preg_match('/^mobile\\' . DIRECTORY_SEPARATOR . '/', $templateFile)) {
                $templateType = Application_Model_Models_Template::TYPE_MOBILE;
            }
            else {
                $templateType = Application_Model_Models_Template::TYPE_REGULAR;
            }

            if (isset($templateType)) {
                // Checking if we have this type in db or adding it
                $checkTypeExists = $templateTypeTable->find($templateType);
                if (!$checkTypeExists->count()) {
                    $checkTypeExists = $templateTypeTable->createRow(array(
                        'id'    => $templateType,
                        'title' => ucfirst(preg_replace('/^type/ui', '', $templateType)).' Template'
                    ));
                    $checkTypeExists->save();
                }
                unset($checkTypeExists);

                $template->setType($templateType);
            }

            // Getting template content
            try {
                $template->setContent(Tools_Filesystem_Tools::getFile($themePath.DIRECTORY_SEPARATOR.$templateFile));
            }
            catch (Exceptions_SeotoasterException $e) {
                array_push($errors, 'Can\'t read template file: ' . $templateName);
            }

            // Saving template to db
            $templateMapper->save($template);
            unset($template, $templateName);
        }
        unset($templateTypeTable);

        if (!empty($errors)) {
            throw new Exception(join('<br />', $errors));
        }
    }

    public static function getThemeIniData($themePath) {
        $themePath = rtrim($themePath, DIRECTORY_SEPARATOR);
        // Trying to get theme.ini file with templates presets
        try {
            $themeIniConfig = parse_ini_string(
                Tools_Filesystem_Tools::getFile(
                    $themePath.DIRECTORY_SEPARATOR.Tools_Template_Tools::THEME_CONFIGURATION_FILE
                )
            );
        }
        catch (Exception $e) {
            $themeIniConfig = array();
        }

        return $themeIniConfig;
    }

    public static function  updateThemeIni($themePath, $key, $val) {
        $themePath      = rtrim($themePath, DIRECTORY_SEPARATOR);
        $themeIniConfig = new Zend_Config(self::getThemeIniData($themePath), true);
        $themeIniConfig->{$key} = $val;

        if (!empty($themeIniConfig)) {
            try {
                $iniWriter = new Zend_Config_Writer_Ini(array(
                    'config'   => $themeIniConfig,
                    'filename' => $themePath.DIRECTORY_SEPARATOR.Tools_Template_Tools::THEME_CONFIGURATION_FILE
                ));
                $iniWriter->write();
            }
            catch (Exception $e) {
                Tools_System_Tools::debugMode() && error_log($e->getMessage());
            }
        }
    }
}
