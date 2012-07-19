<?php

/**
 * Tools
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Tools_System_Tools {

    const REMOTE_TOASTER_URL = 'http://www.seotoaster.com/';

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
        $captcha->getSession()->setExpirationHops(10, null, true);

		return $captcha->setTimeout('300')
            //->setKeepSession(true)
			->setWordLen('5')
			->setHeight(45)
			->setFont($websiteHelper->getPath() . 'system/fonts/Alcohole.ttf')
			->setImgDir($websiteHelper->getPath() . $websiteHelper->getTmp())
            ->setImgUrl($websiteHelper->getUrl() . $websiteHelper->getTmp())
			->setFontSize(20)
		    ->setDotNoiseLevel(0)
		    ->setLineNoiseLevel(0)
		    ->generate();    //command to generate session + create image
		//return $captcha->getId();   //returns the ID given to session image
	}

	public static function arrayToCsv($data, $headers = array()) {
		if(!empty($data)) {
            $websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
            $data[]        = $headers;
            $data          = array_reverse($data);
            $fileName      = 'userslist.' . date("Y-m-d", time()) . '.csv';
            $filePath      = $websiteHelper->getPath() . $websiteHelper->getTmp() . $fileName;
            $expFile       = fopen($filePath, 'w');
			foreach($data as $csvRow) {
                fputcsv($expFile, $csvRow, ',', '"');
			}
            fclose($expFile);
			return $filePath;
		}
		return false;
	}

	public static function getRequestUri() {
		$front = Zend_Controller_Front::getInstance();
		return $front->getRequest()->getParam('page', false);
	}

	public static function getTemplatesHash($type = 'all') {
		$mapper    = Application_Model_Mappers_TemplateMapper::getInstance();
		$hash      = array();
		$templates = array();
		if($type == 'all') {
			$templates = $mapper->fetchAll();
		} else {
			$templates = $mapper->findByType($type);
		}
		if(!empty($templates)) {
			foreach($templates as $template) {
				$hash[$template->getName()] = ucfirst($template->getName());
			}
		}
		return $hash;
	}

	public static function sqlProfiler(){
		if (APPLICATION_ENV !== 'development' || !isset($_COOKIE['_profileSql'])) {
			exit;
		}
		$profiler   = Zend_Db_Table_Abstract::getDefaultAdapter()->getProfiler();
		$totalTime  = $profiler->getTotalElapsedSecs();
		$queryCount = $profiler->getTotalNumQueries();

		$pageUrl = Zend_Controller_Front::getInstance()->getRequest()->getRequestUri();
		$htmlResult  = '<pre id="seotoaster-profiler-out">';
		$htmlResult .= '<h1>'.$pageUrl.'</h1>'.PHP_EOL;
		$htmlResult .= '';
		$htmlResult .= '<table border="1"><thead><tr><th>QUERY</th><th>TIME (sec)</th></tr></thead><tbody>';
		foreach ($profiler->getQueryProfiles() as $query) {
			$htmlResult .= sprintf('<tr><td>%s</td><td>%s</td></tr>', $query->getQuery(), number_format($query->getElapsedSecs(), 6));
		}
		$htmlResult .='</tbody>';
		$htmlResult .='<tfoot><tr><th>TOTAL '.$queryCount.'</th><th>'.number_format($totalTime, 6).'</th></tr></tfoot>';
		$htmlResult .='</pre>';

		$pathToTmp = Zend_Controller_Action_HelperBroker::getExistingHelper('website')->getPath() . 'tmp/';
		$reportName = 'sqlprofile_'.'_pid-'.getmypid().'_'.date('Ymd').'.html';
		try {
			Tools_Filesystem_Tools::saveFile($pathToTmp.$reportName, $htmlResult);
		} catch (Exception $e){

		}

		if (!Zend_Controller_Front::getInstance()->getRequest()->isXmlHttpRequest()){
			echo '<a href="tmp/'.$reportName.'" target="_blank">view sql profile</a>';
		}
	}

    public function fetchSystemtriggers() {
        $triggers      = array();
        $config        = new Zend_Config_Ini(APPLICATION_PATH . '/configs/' . SITE_NAME . '.ini', 'actiontriggers');
        if($config) {
            $triggers = $config->actiontriggers->toArray();
        }
        return $triggers;
    }
}

