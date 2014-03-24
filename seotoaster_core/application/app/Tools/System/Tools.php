<?php

/**
 * Tools
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Tools_System_Tools {

    const REMOTE_TOASTER_URL        = 'http://www.seotoaster.com/';

    const DATE_MYSQL                = 'Y-m-d H:i:s';

    const DEFAULT_UPLOAD_FILESCOUNT = 5;

    const EXECUTION_TIME_LIMIT      = 0;

	const PLACEHOLDER_SYSTEM_VERSION    = 'sysverHolder';

    const RECAPTCHA_PUBLIC_KEY = 'recaptchaPublicKey';

    const RECAPTCHA_PRIVATE_KEY = 'recaptchaPrivateKey';

	public static function getUrlPath($url) {
		$parsedUrl = self::_proccessUrl($url);
		return (isset($parsedUrl['path'])) ? trim($parsedUrl['path'], '/')  . (isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '') : '';
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

	public static function zip($pathToFile, $name = '', $excludeFiles = array()) {

        //extend script execution time limit
        $execTime = ini_get('max_execution_time');
        set_time_limit(self::EXECUTION_TIME_LIMIT);

		$websiteHelper  = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
		$zipArch        = new ZipArchive();
		$files          = array($pathToFile);
		$exploded       = explode('/', $pathToFile);
		$localName      = preg_replace('~\.[\w]+$~', '', end($exploded));
		$destinationFile = $websiteHelper->getPath() . 'tmp/' . (($name) ? $name : $localName) . '.zip';
		if(file_exists($destinationFile)) {
			@unlink($destinationFile);
		}
		if(is_dir($pathToFile)) {
			$files   = Tools_Filesystem_Tools::scanDirectory($pathToFile, true, true);
            $exclude = array();

            foreach($excludeFiles as $excludePath) {
                if(is_dir($excludePath)) {
                    $exclude = array_merge(Tools_Filesystem_Tools::scanDirectory($excludePath, true, true), $exclude);
                } else {
                    array_push($exclude, $excludePath);
                }

            }
            $files = array_diff($files, $exclude);

		}
		$zipArch->open($destinationFile, ZipArchive::OVERWRITE);
		if(!empty ($files)) {
			foreach ($files as $key => $path) {
				$zipArch->addFile($path, substr($path, strpos($path, $localName)));
			}
		}
		$zipArch->close();

        //set back default execution time limit
        set_time_limit($execTime);

		return $destinationFile;
	}

    /**
     * Generate new captcha session occurrence and image
     *
     * @static
     * @return string new captcha id
     */
    public static function generateCaptcha() {
		$websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
		$captcha       = new Zend_Captcha_Image();
		return $captcha->setTimeout('300')
            ->setWordLen('5')
			->setHeight(45)
			->setFont($websiteHelper->getPath() . 'system/fonts/Alcohole.ttf')
			->setImgDir($websiteHelper->getPath() . $websiteHelper->getTmp())
			->setFontSize(20)
		    ->setDotNoiseLevel(0)
		    ->setLineNoiseLevel(0)
		    ->generate();
	}
    
     /**
     * Generate new recaptcha 
     *
     * @static
     * @return recaptcha code
     */
    
    public static function generateRecaptcha($captchaTheme = 'red', $captchaId = null) {
        $websiteConfig = Zend_Controller_Action_HelperBroker::getExistingHelper('config')->getConfig();
        if (!empty($websiteConfig) && !empty($websiteConfig[self::RECAPTCHA_PUBLIC_KEY]) && !empty($websiteConfig[self::RECAPTCHA_PRIVATE_KEY])) {
            $options = array('theme' => $captchaTheme);
            $params = null;
            if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") {
                $params = array(
                    'ssl' => Zend_Controller_Front::getInstance()->getRequest()->isSecure(),
                    'error' => null,
                    'xhtml' => false
                );
            }
            if (null !== $captchaId) {
                $options['custom_theme_widget'] = $captchaId;
            }
            $recaptcha = new Zend_Service_ReCaptcha($websiteConfig[self::RECAPTCHA_PUBLIC_KEY], $websiteConfig[self::RECAPTCHA_PRIVATE_KEY], $params, $options);
            return $recaptcha->getHTML();
        }
        return false;
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

    /**
     * Get requested uri
     *
     * @return string
     */
    public static function getRequestUri() {
		$request = Zend_Controller_Front::getInstance()->getRequest();
        if(($uri = $request->getParam('page', false)) === false) {
            return $request->getRequestUri();
        }
        return $uri;
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

    public static function fetchSystemtriggers() {
        $triggers      = array();
        $config        = new Zend_Config_Ini(APPLICATION_PATH . '/configs/' . SITE_NAME . '.ini', 'actiontriggers');
        if($config) {
            $triggers = $config->actiontriggers->toArray();
        }
        return $triggers;
    }

    public static function debugMode() {
        $misc = Zend_Registry::get('misc');
        if(APPLICATION_ENV == 'development' && isset($misc['debug']) && (boolean)$misc['debug'] == true) {
            return true;
        }
        return false;
    }

    /**
     * Change any OS directory separator to the "/".
     *
     * @param $path string
     * @return string Normalized path
     */
    public static function normalizePath($path) {
        return str_replace(DIRECTORY_SEPARATOR, '/', $path);
    }
    
    public static function getSystemVersion(){
        try {
            return Tools_Filesystem_Tools::getFile('version.txt');
        } catch (Exceptions_SeotoasterException $se) {
            if(self::debugMode()) {
                error_log($se->getMessage());
            }
        }
        return '';
    }

    public static function getAllowedUploadData() {
        $uploadFileSize = intval(ini_get('upload_max_filesize'));
        $postSize       = intval(ini_get('post_max_size'));
        $filesCount     = intval(ini_get('max_file_uploads'));
        return array(
            'fileSize'    => ($uploadFileSize > $postSize) ? $postSize : $uploadFileSize,
            'fileUploads' => ($filesCount) ? $filesCount : self::DEFAULT_UPLOAD_FILESCOUNT
        );
    }

    public static function getMime($file) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file);
        finfo_close($finfo);
        return $mime;
    }

    /**
     * Detect version browser internet explorer.
     *
     * @return bool
     */
    public static function isBrowserIe($notBelowVersion = 9) {
        $version = false;

        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $agent = $_SERVER['HTTP_USER_AGENT'];

            if (preg_match('/MSIE/i', $agent) && !preg_match('/Opera/i', $agent)) {
                $browser = 'MSIE';
                $data    = array();

                preg_match_all(
                    '#(?<browser>Version|'.$browser.'|other)[/ ]+(?<version>[0-9.|a-zA-Z.]*)#',
                    $agent,
                    $data
                );

                if (isset($data['browser']) && count($data['browser']) != 1) {
                    if (isset($data['version'][0]) && strripos($agent, 'Version') < strripos($agent, $browser)) {
                        $version = $data['version'][0];
                    }
                    elseif (isset($data['version'][1])) {
                        $version = $data['version'][1];
                    }
                }
                elseif (isset($data['version'][0])) {
                    $version = $data['version'][0];
                }
            }
        }

        return ($version && intval($version) < $notBelowVersion) ? false : true;
    }
}
