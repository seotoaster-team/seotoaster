<?php

/**
 * Description of Tools
 *
 * @author iamne
 */
class Tools_Filesystem_Tools {

	private static $_excludedFiles = array('.svn', '.', '..', '.htaccess', 'concat.css');

	/**
	 * Scan directory and get all files from it.
	 * Exlude files wich are in the $_excludedFiles from the result
	 *
	 * @param string $path Path to the directory
	 * @return array
	 */
	public static function scanDirectory($path, $incFilePath = false, $recursively = true) {
		$foundFiles = array();
		$path       = (string)trim($path = (substr($path, strlen($path)-1) == DIRECTORY_SEPARATOR) ? $path : $path . DIRECTORY_SEPARATOR);
		if(!$path) {
			throw new Exceptions_SeotoasterException('Scaning directory: path to the directrory is empty.');
		}
		if(!is_dir($path)) {
			throw new Exceptions_SeotoasterException('Scaning directory: path is not a directrory.');
		}
		$foundFiles = @scandir($path);
		$files = array();
		if(is_array($foundFiles) && !empty ($foundFiles)) {
			foreach ($foundFiles as $key => $file) {
				if(in_array($file, self::$_excludedFiles)) {
					unset($foundFiles[$key]);
					continue;
				}
				if($recursively) {
					if(is_dir($path . $file)) {
						unset($foundFiles[array_search($file, $foundFiles)]);
						//$foundFiles = array_merge($foundFiles, self::scanDirectory($path . '/' . $file, $incFilePath));
						$files = array_merge($files, self::scanDirectory($path . $file, $incFilePath));
						continue;
					}
				}
				if($incFilePath) {
					//$foundFiles[$key] = $path . '/' . $file;
					array_push($files, $path . $file);
				} else {
					array_push($files, $file);
				}
			}
		}
		return $files;
	}

	public static function scanDirectoryForDirs($path) {
		$foundDirs = array();
		$path      = (string)trim($path);
		if(!$path) {
			throw new Exceptions_SeotoasterException('Scaning directory: path <strong>(' .$path . ')</strong> to the directrory is empty.');
		}
		if(!is_dir($path)) {
			throw new Exceptions_SeotoasterException('Scaning directory: path <strong>(' .$path . ')</strong> is not a directrory.');
		}
		$foundDirs = @scandir($path);
		if(!empty ($foundDirs)) {
			foreach ($foundDirs as $key => $directory) {
				if(!is_dir($path .'/'. $directory) || in_array($directory, self::$_excludedFiles)) {
					unset ($foundDirs[$key]);
				}
			}
		}
		return $foundDirs;
	}

	/**
	 * Scan given directory for files with given extension
	 *
	 * @todo recode this method to use "glob" php function
	 *
	 * @param string $directory Directory to scan
	 * @param string $extension Files extension
	 * @return array
	 */
	public static function findFilesByExtension($directory, $extension, $incFilePath = false, $pairs = false, $recursively = true) {
		$foundFiles = array();
		$files      = self::scanDirectory($directory, $incFilePath, $recursively);
		if(!empty($files)) {
			if(is_array($extension)) {
				$extension = implode('|', $extension);
			}
			foreach ($files as $file) {
				$fileMatch = (extension_loaded('mbstring')) ? mb_eregi('^.+\.(' . $extension . ')$', $file) : preg_match('~^.+\.' . $extension . '$~uiU', $file);
				if($fileMatch) {
					if($pairs) {
						$explodedFilePath = explode(DIRECTORY_SEPARATOR, $file);
						$foundFiles[preg_replace('~\.[a-zA-Z]{3,4}~iu', '', end($explodedFilePath))] = $file;
					}
					else {
						$foundFiles[] = $file;
					}
				}
			}
		}
		return $foundFiles;
	}

	public static function saveFile($path, $content) {
		if(false === @file_put_contents($path, $content)) {
			throw new Exceptions_SeotoasterException('Unable to save file: ' . $path . ' check permissions.');
		}
	}

	public static function getFile($filename){
		if (!file_exists($filename)) {
			throw new Exceptions_SeotoasterException('File doesn\'t exists');
		}
		return file_get_contents($filename);
	}

	public static function deleteFile($filename) {
		$filename = trim($filename);
		if ($filename == ''){
			return false;
		}
		if (file_exists($filename)){
			return @unlink($filename);
		} else {
			throw new Exceptions_SeotoasterException('File doesn\'t exists');
		}
	}

	public static function deleteDir($dirname) {
		$dirname = trim($dirname);
		if ($dirname == ''){
			return false;
		}
		if (is_dir($dirname)){
			$listFiles = self::scanDirectory($dirname, true);
			foreach ($listFiles as $file){
				self::deleteFile($file);
			}
			$subdirs = self::scanDirectoryForDirs($dirname);
			foreach ($subdirs as $subdir){
				self::deleteDir($dirname.'/'.$subdir);
			}
			return @rmdir($dirname);
		} else {
			return false;
		}
	}

	public static function mkDir($dirname){
		$dirname = (string) trim($dirname);

		if($dirname === '') {
			return false;
		}

		if(!is_dir($dirname)) {
			return @mkdir($dirname);
		} else {
			return false;
		}
	}

	public static function basename($filepath) {
		$filepath = (string) trim($filepath);
		if (!$filepath){
			return false;
		}

		preg_match('~[^'.DIRECTORY_SEPARATOR.']+$~i', $filepath, $match);

		if ($match) {
			return $match[0];
		}

		return false;
	}
}
