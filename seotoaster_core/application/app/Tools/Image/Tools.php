<?php
/**
 * Tools
 *
 * @author Pavel Kovalyov <pavlo.kovalyov@gmail.com>
 */
class Tools_Image_Tools {
	public static $imgResizedFolders = array(
		'small',
		'medium',
		'large',
		'product'
	);

	public function init() {
		if (!Zend_Registry::isRegistered('extConfig')) {
			$configTable   = new Application_Model_DbTable_Config();
			Zend_Registry::set('extConfig', $configTable->selectConfig());
		}
	}
	
	public static function resize($imageFile, $newWidth,  $saveProportion = true, $destination = null) {
		if ( !$imageFile  || !$newWidth){
			return 'Missing parameters';
		}
		
		if (!is_file($imageFile) || !is_readable($imageFile)){
			return 'No file specified';
		}
		
		$iniConfig = Zend_Registry::get('misc');
		//setting quality
		$quality = isset($iniConfig['img_quality']) ? $iniConfig['img_quality'] : 90;
        $pngQuality = floor((100-$quality)/10);
		
		$fileInfo	= getimagesize($imageFile);
		$imgWidth	= $fileInfo[0];
		$imgHeight	= $fileInfo[1];
		$fileType	= $fileInfo[2];
		$mimeType	= $fileInfo['mime'];
		
		if ($imgWidth > $newWidth){
			if ($saveProportion) {
				$newHeight = $imgHeight * $newWidth / $imgWidth;
			} else {
				$newHeight = $newWidth;
			}
		} else {
			// if the original size less then it needs to resized at
			// copying original file to destination and exiting
			if ($destination){
				copy($imageFile, $destination.DIRECTORY_SEPARATOR.Tools_Filesystem_Tools::basename($imageFile));
			}
			return true;
		}
		
		$newImage = imagecreatetruecolor($newWidth, $newHeight);
		
		$saveAlphaChannel = false;
		switch ($mimeType) {
			case 'image/gif':
				$image = imagecreatefromgif($imageFile);
				$saveAlphaChannel = true;
				break;
			case 'image/jpg':
			case 'image/jpeg':
				$image = imagecreatefromjpeg($imageFile);
				break;
			case 'image/png':
				$image = imagecreatefrompng($imageFile);
				$saveAlphaChannel = true;
				break;
			default:
				return 'Unknow MIME type';
				break;
		}
		
		// fix for transparency
		if ($saveAlphaChannel) {
			imagealphablending($newImage, false);
			imagesavealpha($newImage, true);
			$transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
			imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
		}
		
		imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $imgWidth, $imgHeight);
		
		if ($destination) {
			if (!is_dir($destination)){
				Tools_Filesystem_Tools::mkDir($destination);
			}
			$imageFile = $destination . DIRECTORY_SEPARATOR . Tools_Filesystem_Tools::basename($imageFile);
		}
		
		switch ($mimeType) {
			case 'image/gif':
				imagegif($newImage, $imageFile);
				break;
			case 'image/jpeg':
				imagejpeg($newImage, $imageFile, $quality);
				break;
			case 'image/png':
				imagepng($newImage, $imageFile, $pngQuality);
				break;
			default:
				return 'Unknow MIME type';
				break;
		}
		imagedestroy($newImage);
		imagedestroy($image);
		
		return true;
	}
	
	/**
	 * Batch resize for image upload proccess
	 * @param string original file
	 * @param string desination of resized files
	 * @return boolean|array true-on success, array when errors occur
	 */
	public static function batchResize($imageFile, $destination) {
		$imageFile = trim($imageFile);
		$destination = trim($destination);
		
		if (empty($imageFile) || empty ($destination)){
			return false;
		}
		
		$dbConfig = Zend_Registry::get('extConfig');
		$iniConfig = Zend_Registry::get('misc');
		
		$sizeConfig = array(
			'small'	 => intval($dbConfig['img_small']),
			'medium' => intval($dbConfig['img_medium']),
			'large'	 => intval($dbConfig['img_large']),
			'product' => intval($iniConfig['img_product'])
		);
		
		$errors = array();
		
		foreach ($sizeConfig as $type => $size){
			if (!is_dir($destination.DIRECTORY_SEPARATOR.$type)){
				Tools_Filesystem_Tools::mkDir($destination.DIRECTORY_SEPARATOR.$type);
			}
			$result = self::resize($imageFile, $size, true, $destination.DIRECTORY_SEPARATOR.$type );
			if ($result !== true){
				array_push($errors, $result);
			}
		}
		
		return empty($result) ? true : $result;
	}
	
	/**
	 * Method removes images with given name in the given directory via recursive scan of subfolders 
	 * such as, small, medium, etc.
	 * @param string $imageName Name of image to be deleted
	 * @param string $folderName Name of folder where image is
	 * @return mixed  Boolean true on success of all operations array with errors 
	 * @return mixed  Boolean false on empty parameters given
	 * @return mixed  Array with errors if something went wrong
	 */
	public static function removeImageFromFilesystem($imageName, $folderName) {
		$imageName = trim($imageName);
		$folderName = trim($folderName);
		if (empty ($imageName) || empty ($folderName)){
			return false;
		}
		
		$websiteConfig = Zend_Registry::get('website');
		
		$folderPath = $websiteConfig['path'].$websiteConfig['media'].$folderName;
		if (!is_dir($folderPath)) {
			throw new Exceptions_SeotoasterException('Wrong folder name specified');
		}
		
		$errorCount = 0;
		
		$subFoldersList = array_merge(self::$imgResizedFolders, array('original'));
		
		//list of file that can be removed
		$removable = array();
		
		foreach ($subFoldersList as $subfolder) {
			if (!is_dir($folderPath.DIRECTORY_SEPARATOR.$subfolder)){
				error_log('Not a folder:'.$folderPath.DIRECTORY_SEPARATOR.$subfolder);
				continue;
			}
			$filename = $folderPath.DIRECTORY_SEPARATOR.$subfolder.DIRECTORY_SEPARATOR.$imageName;
			//checking if enough permission to remove file
			if (is_file($filename) && is_writable($filename)) {
				array_push($removable, $filename);	
			}
		}
		
		/**
		 * checking if we can remove all files at once
		 * if not - returning with error
		 */
		if (sizeof($removable) === sizeof($subFoldersList)){
			foreach ($removable as $file) {
				try {
					Tools_Filesystem_Tools::deleteFile($file);
				} catch (Exceptions_SeotoasterException $e) {
					$errorCount++;
					error_log($file.': '. $e->getMessage() );
				}
			}
			if ($errorCount){
				return false;
			}
			return true;
		} 
		
		return 'Permission denied';			
	}
	
}