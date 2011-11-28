<?php

/**
 * Tools
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Tools_System_Tools {

	public static function getUrlPath($url) {
		$parsedUrl = self::_proccessUrl($url);
		return (isset($parsedUrl['path'])) ? trim($parsedUrl['path'], '/') : 'index.html';
	}

	public static function getUrlScheme($url) {
		$parsedUrl = self::_proccessUrl($url);
		return strtolower($parsedUrl['scheme']);
	}

	public static function getUrlHost($url) {
		$parsedUrl = self::_proccessUrl($url);
		return $parsedUrl['host'];
	}

	private static function _proccessUrl($url) {
		try {
			$uri = Zend_Uri::factory($url);
		}
		catch(Exception $e) {
			$url = 'http://' . $url;
			$uri = Zend_Uri::factory('http://' . $url);
		}
		if(!$uri->valid()) {
			throw new Exceptions_SeotoasterException($url . ' is not valid');
		}
		return parse_url($url);
	}

	public static function bobbleSortDeeplinks($deeplinks) {
		$arraySize = count($deeplinks) - 1;
		for($i = $arraySize; $i >= 0; $i--) {
			for($j = 0; $j <= ($i-1); $j++) {
				if(strlen($deeplinks[$j]->getName()) < strlen($deeplinks[$j+1]->getName())) {
					$tmp = $deeplinks[$j];
					$deeplinks[$j] = $deeplinks[$j+1];
					$deeplinks[$j+1] = $tmp;
				}
			}
		}
		return $deeplinks;
	}

	public static function cutExtension($string){
		$exploded = explode('.', $string);
		unset($exploded[sizeof($exploded) - 1]);
		return implode('', $exploded);
	}

	public static function zip($pathToFile, $name = '') {
		$websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
		$zipArch       = new ZipArchive();
		$files         = array($pathToFile);
		$exploded      = explode(DIRECTORY_SEPARATOR, $pathToFile);
		$localName     = preg_replace('~\.[\w]+$~', '', end($exploded));
		$destinationFile = $websiteHelper->getPath() . 'tmp/' . (($name) ? $name : $localName) . '.zip';
		if(file_exists($destinationFile)) {
			@unlink($destinationFile);
		}
		if(is_dir($pathToFile)) {
			$files = Tools_Filesystem_Tools::scanDirectory($pathToFile, true, true);
		}
		$zipArch->open($destinationFile, ZipArchive::OVERWRITE);
		if(!empty ($files)) {
			foreach ($files as $key => $path) {
				$zipArch->addFile($path, substr($path, strpos($path, $localName)));
			}
		}
		$zipArch->close();
		return $destinationFile;
	}

	public static function generateCaptcha() {
		$websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');

		$captcha = new Zend_Captcha_Image();
		$captcha->setTimeout('300')
			->setWordLen('5')
			->setHeight(45)
			->setFont($websiteHelper->getPath() . 'system/fonts/Alcohole.ttf')
			->setImgDir($websiteHelper->getPath() . $websiteHelper->getTmp())
			->setFontSize(20);
		$captcha->setDotNoiseLevel(0);
		$captcha->setLineNoiseLevel(0);
		$captcha->generate();    //command to generate session + create image
		return $captcha->getId();   //returns the ID given to session image
	}

	public static function arrayToCsv($data) {
		if(!empty($data)) {
			foreach($data as $csvRow) {

			}
		}
	}
}

