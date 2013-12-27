<?php
/**
 * Theme
 *
 * @author Eugene I. Nezhuta <theneiam@gmail.com>
 *         Date: 12/18/12
 *         Time: 2:14 AM
 */
class Tools_Theme_Tools {

	public static $requiredFiles = array(
		'index.html',
		'default.html',
		'category.html',
		'style.css',
		'content.css'
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
        $filePath      = $websiteHelper->getPath().$themesPath.MagicSpaces_Concatcss_Concatcss::FOLDER_CSS.'content.css';
        $folderCssPath = (is_file($filePath)) ? MagicSpaces_Concatcss_Concatcss::FOLDER_CSS : '';

        return $websiteHelper->getUrl().$themesPath.$folderCssPath.'content.css';
    }
}
