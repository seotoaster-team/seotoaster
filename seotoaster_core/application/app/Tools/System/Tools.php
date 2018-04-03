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

    const GRECAPTCHA_PUBLIC_KEY = 'grecaptchaPublicKey';

    const GRECAPTCHA_PRIVATE_KEY = 'grecaptchaPrivateKey';

    const CSRF_SECURE_TOKEN = 'secureToken';

    const ACTION_PREFIX_CONFIG = 'Config';

    const ACTION_PREFIX_CONTAINERS = 'Containers';

    const ACTION_PREFIX_PAGES = 'Pages';

    const ACTION_PREFIX_USERS = 'Users';

    const ACTION_PREFIX_LOGIN = 'Login';

    const ACTION_PREFIX_FORMS = 'Forms';

    const ACTION_PREFIX_ROBOTS = 'Robots';

    const ACTION_PREFIX_REDIRECTS = 'Redirects';

    const ACTION_PREFIX_DEEPLINKS = 'Deeplinks';

    const ACTION_PREFIX_SILOS = 'Silos';

    const ACTION_PREFIX_TEMPLATES = 'Templates';

    const ACTION_PREFIX_EDITCSS = 'Editcss';

    const ACTION_PREFIX_EDITJS = 'Editjs';

    const ACTION_PREFIX_EDITREPEAT = 'EditRepeat';

    const ACTION_PREFIX_ACTIONEMAILS = 'ActionEmails';

    const ACTION_PREFIX_FAREA = 'Farea';

    const ACTION_PREFIX_REMOVETHINGS = 'RemoveThings';

    const ACTION_PREFIX_ORGANIZEPAGES = 'OrganizePages';

    const ACTION_PREFIX_PLUGINS = 'Plugins';

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
		$zipArch->open($destinationFile, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);
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

    /**
     * Fetch system action emails triggers
     *
     * @return array
     */
    public static function fetchSystemtriggers()
    {
        $triggers = array();
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/' . SITE_NAME . '.ini', 'actiontriggers');
        if ($config) {
            $triggers = $config->actiontriggers->toArray();
            $additionalSystemTriggersPath = Zend_Controller_Action_HelperBroker::getExistingHelper('website')->getPath() . 'system'.DIRECTORY_SEPARATOR.'system-action-emails.ini';
            if (file_exists($additionalSystemTriggersPath)) {
                $additionalSystemTriggers = new Zend_Config_Ini($additionalSystemTriggersPath, 'actiontriggers');
                if (!empty($additionalSystemTriggers) && !empty($triggers['seotoaster']['trigger'])) {
                    $additionalSystemTriggers = $additionalSystemTriggers->actiontriggers->toArray();
                    if (!empty($additionalSystemTriggers['seotoaster']) && !empty($additionalSystemTriggers['seotoaster']['trigger'])) {
                        $triggers['seotoaster']['trigger'] = array_merge($triggers['seotoaster']['trigger'],
                            $additionalSystemTriggers['seotoaster']['trigger']);
                    }
                }
            }
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

    public static function getCountryPhoneCodesList($withCountryCode = true, $intersect = array(), $withoutCache = false) {
        if ($withoutCache === true) {
            return self::processPhoneCodes($withCountryCode, $intersect);
        }
        $cache       = Zend_Controller_Action_HelperBroker::getStaticHelper('Cache');
        $cachePrefix = strtolower(__CLASS__).'_';
        $cacheId     = strtolower(__FUNCTION__) . '_' . (int)$withCountryCode . '_' . json_encode($intersect);
        if (null === ($phoneCodes = $cache->load($cacheId, $cachePrefix))) {
            $phoneCodes = self::processPhoneCodes($withCountryCode, $intersect);
            $cache->save($cacheId, $phoneCodes, $cachePrefix, array(), Helpers_Action_Cache::CACHE_SHORT);
        }
        return $phoneCodes;

    }

    /**
     * Process phone codes
     *
     * @param bool $withCountryCode with country code flag
     * @param array $intersect intersect with params
     * @return array
     */
    public static function processPhoneCodes($withCountryCode, $intersect)
    {
        $phoneCodes = Zend_Locale::getTranslationList('phoneToTerritory');
        array_shift($phoneCodes);
        if(!empty($intersect)) {
            $phoneCodes = array_intersect_key($phoneCodes, array_flip($intersect));
        }
        array_walk($phoneCodes, function(&$item, $key) use($withCountryCode) {
            $item = ($withCountryCode) ? '+' . $item . ' ' . $key : '+' . $item;
        });

        return $phoneCodes;
    }

    public static function getWebsiteCountryCode() {
        $countryCode = 'US';
        $plugins = Application_Model_Mappers_PluginMapper::getInstance()->findByName('shopping');
        if(!empty($plugins) && $plugins->getStatus() === 'enabled') {
            $storeCountry = Models_Mapper_ShoppingConfig::getInstance()->getConfigParam('country');
            if(!empty($storeCountry)) {
                return $storeCountry;
            }
        }
        $configHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('config');
        $widcardCountry = $configHelper->getConfig('wicOrganizationCountry');
        if(!empty($widcardCountry)) {
            return $widcardCountry;
        }
        return $countryCode;
    }

    public static function makeSpace($content)
    {
        return preg_replace("/[^A-Za-z0-9 ]/", '&nbsp;', $content);
    }

    /**
     * Generate unique token
     *
     * @param string $salt prefix for code generation
     * @return string
     */
    public static function generateSecureToken($salt)
    {
        return md5(
            mt_rand(1, 1000000)
            . $salt
            . mt_rand(1, 1000000)
        );
    }

    /**
     * Init secure token in session with specified prefix if not exists
     *
     * @param string $tokenPrefix prefix for token in session
     * @param bool $regenerate if true force regenerate token
     * @return string
     */
    public static function initSecureToken($tokenPrefix, $regenerate = false)
    {
        $sessionHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('session');
        $tokenName = self::CSRF_SECURE_TOKEN . $tokenPrefix;
        if (isset($sessionHelper->$tokenName) && !$regenerate) {
            return $sessionHelper->$tokenName;
        }
        $secureToken = self::generateSecureToken($tokenPrefix);
        $sessionHelper->$tokenName = $secureToken;
        return $secureToken;
    }

    /**
     * Validate token. If token exists and matched return true
     *
     * @param string $token secure token
     * @param string $tokenPrefix token prefix
     * @return bool
     */
    public static function validateToken($token, $tokenPrefix = '')
    {
        $sessionHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('session');
        $tokenName = self::CSRF_SECURE_TOKEN . $tokenPrefix;
        if (!isset($sessionHelper->$tokenName)) {
            return false;
        }
        if ($sessionHelper->$tokenName !== $token) {
            return false;
        }
        return true;
    }

    /**
     * Add custom token value for zend form element
     * Check existing token in session if exists then apply it
     * zend validator Identical to this form element
     *
     * @param Zend_Form $form form
     * @param string $tokenPrefix prefix for secure token
     * @param string $elementName Zend form element name
     * @return Zend_Form
     * @throws Zend_Form_Exception
     */
    public static function addTokenValidatorZendForm(Zend_Form $form, $tokenPrefix = '', $elementName = self::CSRF_SECURE_TOKEN)
    {
        $sessionHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('session');
        $tokenName = self::CSRF_SECURE_TOKEN . $tokenPrefix;
        if (isset($sessionHelper->$tokenName)) {
            $form->getElement($elementName)->removeValidator('Identical');
            $form->getElement($elementName)->addValidator(
                'Identical',
                false,
                array('token' => $sessionHelper->$tokenName)
            );
        }
        return $form;
    }

    /**
     * Init csrf token for zend form
     * Check existing token in session if exists then apply it
     *
     * @param Zend_Form $form form
     * @param string $tokenPrefix prefix for secure token
     * @param string $elementName Zend form element name
     * @return string
     */
    public static function initZendFormCsrfToken(Zend_Form $form, $tokenPrefix = '', $elementName = self::CSRF_SECURE_TOKEN)
    {
        $sessionHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('session');
        $tokenName = self::CSRF_SECURE_TOKEN . $tokenPrefix;
        if (!isset($sessionHelper->$tokenName)) {
            $form->getElement($elementName)->initCsrfToken();
            $secureToken = $form->getElement($elementName)->getValue();
            $sessionHelper->$tokenName = $secureToken;
            return $secureToken;
        }
        return $sessionHelper->$tokenName;
    }

    /**
     * @param $email
     * @param $userId
     * @param string $expireIn
     * @return Application_Model_Models_PasswordRecoveryToken|bool
     */
    public static function saveResetToken ($email, $userId, $expireIn = '+1 day') {
        $resetToken = new Application_Model_Models_PasswordRecoveryToken(array(
            'saltString' => $email,
            'expiredAt'  => date(Tools_System_Tools::DATE_MYSQL, strtotime($expireIn, time())),
            'userId'     => $userId
        ));
        $resetTokenId = Application_Model_Mappers_PasswordRecoveryMapper::getInstance()->save($resetToken);
        if ($resetTokenId) {
            return $resetToken;
        }
        return false;
    }

    /**
     * Find user timezone by id.
     * If id does not provided then timezone based on logged user id will be returned
     *
     * @param int $userId system user id
     * @return string
     */
    public static function getUserTimezone($userId = 0)
    {
        if (empty($userId)) {
            $sessionHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('session');
            $userId = $sessionHelper->getCurrentUser()->getId();
        }

        if (empty($userId)) {
            return 'UTC';
        }
        $userModel = Application_Model_Mappers_UserMapper::getInstance()->find($userId);
        if (!$userModel instanceof Application_Model_Models_User) {
            return 'UTC';
        }

        $userTimeZone = $userModel->getTimezone();

        if (empty($userTimeZone)) {
            return 'UTC';
        }

        return $userTimeZone;
    }

    /**
     * Get offset of UTC
     *
     * @param string $format format to
     * @return string
     */
    public static function getUtcOffset($format)
    {
        $userTimeZone = new DateTimeZone(self::getUserTimezone());
        $currentUserDate = new DateTime('now', $userTimeZone);
        return $currentUserDate->format($format);
    }

    /**
     * Convert date from one to another timezone
     *
     * @param string $date date
     * @param bool $timezoneFrom valid timezone name America/Los_Angeles
     * @param string $timezoneTo valid timezone name America/Los_Angeles
     * @param string $format date format
     * @return string
     */
    public static function convertDateFromTimezone($date, $timezoneFrom = false, $timezoneTo = 'UTC', $format = self::DATE_MYSQL)
    {
        if (empty($timezoneFrom)) {
            $timezoneFrom = self::getUserTimezone();
        }
        $date = new DateTime($date, new DateTimeZone($timezoneFrom));
        $date->setTimezone(new DateTimeZone($timezoneTo));

        return $date->format($format);
    }


    /**
     * get country phone codes list
     *
     * @param bool $withCountryCode flag to use country code
     * @param array $intersect
     * @param bool $reverseLabels change order for labels  Ex: Ascension Island +247
     * @return array
     */
    public static function getFullCountryPhoneCodesList($withCountryCode = true, $intersect = array(), $reverseLabels = false)
    {
        $phoneCodes = Zend_Locale::getTranslationList('phoneToTerritory');
        $countryCodes = Zend_Locale::getTranslationList('Territory');
        array_shift($phoneCodes);
        if (!empty($intersect)) {
            $phoneCodes = array_intersect_key($phoneCodes, array_flip($intersect));
        }
        array_walk($phoneCodes, function (&$item, $key) use ($withCountryCode, $countryCodes, $reverseLabels) {
            if ($reverseLabels === true) {
                $item = ($withCountryCode) ? $countryCodes[$key].' +' . $item : '+' . $item;
            } else {
                $item = ($withCountryCode) ? '+' . $item . ' ' . $countryCodes[$key] : '+' . $item;
            }
        });

        if ($reverseLabels === true) {
            asort($phoneCodes);
        }

        return $phoneCodes;

    }

    /**
     * Reassign zend form fields
     *
     * @param Zend_Form $form zend form
     * @param array $formFields form fields
     * @param array $mandatoryFields mandatory fields
     * @param bool $keepHiddenFields keep hidden fields
     * @return Quote_Forms_Quote
     * @throws Zend_Form_Exception
     */
    public static function adjustFormFields(Zend_Form $form, $formFields = array(), $mandatoryFields = array(), $keepHiddenFields = true)
    {
        if (empty($formFields)) {
            return $form;
        }

        $currentElements = $form->getElements();

        // fields that should stay
        $fields = array();
        foreach ($formFields as $field) {
            $required = false;
            if (substr($field, strlen($field) - 1) == '*') {
                $required = true;
                $field = str_replace('*', '', $field);
            }
            $fields[$field] = $required;
        }

        foreach ($currentElements as $element) {
            $elementType = $element->getType();
            if ($keepHiddenFields === true && $elementType === 'Zend_Form_Element_Hidden') {
                continue;
            }

            $form->removeElement($element->getName());
        }

        $fields = array_merge($fields, $mandatoryFields);
        $i = 1;
        foreach ($fields as $name => $required) {
             if (!array_key_exists($name, $currentElements)) {
                continue;
            }
            $currentElements[$name]->setAttribs(array(
                'class' => ($required) ? 'required' : 'optional'
            ))->setRequired($required);
            $form->addElement($currentElements[$name])->setOrder($i);
            ++$i;
        }

        $displayGroups = $form->getDisplayGroups();
        array_walk($displayGroups, function ($dGroup) use ($form) {
            $form->removeDisplayGroup($dGroup->getName());
        });

        return $form;
    }

    /**
     * Remove all non digits
     *
     * @param string $number
     * @return mixed
     */
    public static function cleanNumber($number)
    {
        return preg_replace('~[^\d]~ui', '', $number);
    }

    /**
     * Returned array of results from each plugin by specified method
     * @param string $tag plugin tag
     * @param string $method method name
     * @return array
     */
    public static function firePluginMethod($tag, $method){
        $result = array();
        $availablePlugins = Tools_Plugins_Tools::getPluginsByTags(array($tag));
        if (!empty($availablePlugins)) {
            foreach ($availablePlugins as $plugin) {
                $pluginClassName = $plugin->getName();
                $pluginClass = new Zend_Reflection_Class($pluginClassName);
                $pluginActionExists = $pluginClass->hasMethod($method);
                if (!$pluginActionExists) {
                    continue;
                }
                $verifyAction = $pluginClass->getMethod($method);
                if (!$verifyAction->isStatic()) {
                    continue;
                }
                $result[$pluginClassName] = $pluginClassName::$method();
            }
        }

        return $result;
    }

    /**
     * Fire plugin method by plugin name
     *
     * @param string $pluginName plugin name
     * @param string $method plugin method
     * @param array $data data array
     * @param bool $static flag for the method type
     * @return array
     * @throws Zend_Reflection_Exception
     */
    public static function firePluginMethodByPluginName($pluginName, $method, $data = array(), $static = true){
        $pluginMapper = Application_Model_Mappers_PluginMapper::getInstance();
        $pluginModel = $pluginMapper->findByName($pluginName);
        if ($pluginModel instanceof Application_Model_Models_Plugin) {
            $status = $pluginModel->getStatus();
            if ($status === Application_Model_Models_Plugin::ENABLED) {
                $pluginClassName = $pluginModel->getName();
                $pluginClass = new Zend_Reflection_Class($pluginClassName);
                $pluginActionExists = $pluginClass->hasMethod($method);
                if ($pluginActionExists === true) {
                    $verifyAction = $pluginClass->getMethod($method);
                    if (!$verifyAction->isStatic() && $static === true) {
                        try {
                            $result = $pluginClassName::$method();
                        } catch (Exception $e) {
                            return array('error' => 1, 'message' => $e->getMessage());
                        }
                    } else {
                        $websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
                        $pageData = array('websiteUrl' => $websiteHelper->getUrl());
                        try {
                            $plugin = Tools_Factory_PluginFactory::createPlugin($pluginName, array(),
                                $pageData);
                            $result = $plugin->$method($data);
                        } catch (Exception $e) {
                            return array('error' => 1, 'message' => $e->getMessage());
                        }
                    }

                } else {
                    return array('error' => 1, 'message' => 'Plugin method doesn\'t exist');
                }
            } else {
                return array('error' => 1, 'message' => 'Plugin disabled');
            }
        } else {
            return array('error' => 1, 'message' => 'Plugin doesn\'t exist');
        }

        return $result;

    }

    /**
     * @param string $email email address
     * @return bool
     */
    public static function isEmailValid($email)
    {
        $email = filter_var($email, FILTER_VALIDATE_EMAIL);
        if (!empty($email)) {
            return true;
        }

        return false;
    }

}
