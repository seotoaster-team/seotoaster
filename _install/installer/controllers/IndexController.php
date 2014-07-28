<?php

/**
 * IndexController
 *
 * @author Pavel Kovalyov <pavlo.kovalyov@gmail.com>
 */
class IndexController extends Zend_Controller_Action {
	
	private $_session		= null;
	private $_requirements	= array();

	public function init() {
		if ($this->_session === null) {
			$this->_session = Zend_Registry::get('session');
		}
		$this->_requirements = Zend_Registry::get('requirements');
		$this->view->messages = array();
	}

	public function indexAction() {
		if ($this->getRequest()->getParam('garbage')){
			$this->redirect('');
		}
		$translator = Zend_Registry::get('Zend_Translate');
		
		if (!$this->getRequest()->isPost()) {
			if (Zend_Session::sessionExists()){
				$namespace = $this->_session->getNamespace();
				if (isset($_SESSION[$namespace])) {
					unset ($_SESSION[$namespace]);
				}
				$translator->setLocale('en');
				Zend_Registry::set('Zend_Translate', $translator);
				Zend_Session::regenerateId();
			}
		} else {
			$lang = $this->getRequest()->getParam('lang');
			if ($lang && Zend_Locale::isLocale($lang)){
				$this->_session->locale->setLocale($lang);
				if ($translator->getLocale() !== $lang){
					$translator->setLocale($lang);
					Zend_Registry::set('Zend_Translate', $translator);
				}
				$this->_session->nextStep = 1;
			}
			if ($this->_session->nextStep !== null) {
			    return $this->forward('step'.$this->_session->nextStep);
			}
		}
		
		$this->forward('step1');
	}
	
	public function step1Action() {
		$this->_session->nextStep = 1;
		
		$this->view->langs = $this->_findLanguages();
		
		$phpRequirements = array();
		$permissions = array(
			'dir'	=> array(),
			'file'	=> array(),
			'core'	=> array()
		);
		$permissionsFail = false;
			
		//check for PHP version
		$phpRequirements['php'] = version_compare(PHP_VERSION, $this->_requirements['minPHPVersion'], '>=');

		//check for disabled magic quotes
		$phpRequirements['magicquotes'] = !get_magic_quotes_gpc();
		
		//checking if required libraries are installed
		foreach ($this->_requirements['phpExtensions'] as $name) {
            // php 5.5.x specific check for json extension
            if(($name === 'json') && (version_compare(PHP_VERSION, '5.5', '>=') < 0)) {
                continue;
            }
			$phpRequirements[$name] = extension_loaded($name);
		}
		
		//checking if folders has good permissions
		foreach ($this->_requirements['permissions']['dir'] as $dirname) {
			$dirpath = INSTALL_PATH . DIRECTORY_SEPARATOR . $dirname;

			if (!is_dir($dirpath)){
				try {
					if (@mkdir($dirpath)){
						$permissions['dir'][$dirname] = 'writable';
					} else {
						$permissions['dir'][$dirname] = 'doesn\'t exist';
						$permissionsFail = true;
					}
				} catch (Exception $e) {
					error_log($e->getMessage());
				}
			} else {
				if (is_writable($dirpath)){
					$permissions['dir'][$dirname] = 'writable';
				} else {
					$permissions['dir'][$dirname] = 'not writable';
					$permissionsFail = true;
				}
			}
			
			unset($dirpath);
		}
		
		//checking for permissions for necessary files
		foreach ($this->_requirements['permissions']['file'] as $filename) {
			$filepath = INSTALL_PATH . DIRECTORY_SEPARATOR . $filename;
			if (!is_file($filepath)){
				$permissions['file'][$filename] = 'not exists';
				$permissionsFail = true;
			} elseif (!is_writable($filepath)){
				$permissions['file'][$filename] = 'not writable';
				$permissionsFail = true;
			} else {
				$permissions['file'][$filename] = 'writable';
			}
		}
		
		
		if (!in_array(false, $phpRequirements) && !$permissionsFail) {
			$this->_session->nextStep = 2;
			$this->view->gotoNext = true;
//			$this->getRequest()->isPost() && $this->_forward('step2');
		} else {
			$this->view->gotoNext = false;
		}
		$this->view->failedPermissions	= $permissionsFail;
		$this->view->permissions		= $permissions;
		$this->view->checks				= $phpRequirements;	
	}
	
	public function step2Action(){
		$this->_session->nextStep = 2;

		$configForm = new Installer_Form_Config();
				
		$this->view->gotoNext = false;
		$this->view->messages = array();

		$isDbReady	 = false;
		$isCoreValid = false;
		
        //default values
		if (!isset($this->_session->coreinfo)) {
			$this->_session->coreinfo = array( 'corepath' => realpath(INSTALL_PATH . '/seotoaster_core'), 'sitename' => '' );
		}
		$configForm->populate($this->_session->coreinfo);

		if (isset($this->_session->dbinfo) && !empty($this->_session->dbinfo['params'])) {
			$configForm->populate($this->_session->dbinfo['params']);
			$isDbReady = true;
		}
		
		$params = $this->getRequest()->getParams();
		
		if ($this->getRequest()->isPost() && isset($params['check']) && $params['check'] === 'config'){
			
				if (true === ($formValid = $configForm->isValid($params))){
					$formValues = $configForm->getValues();
					$coreinfo = array(
						'corepath'	=> $configForm->getValue('corepath'),
						'sitename'	=> $configForm->getValue('sitename')
					);

					$isCoreValid = true;
					if ($this->_session->coreinfo !== $coreinfo){
						$this->_session->coreinfo = $coreinfo;
					}

					if (!$isDbReady) {
						unset($this->_session->dbinfo);
						$dbParams = array(
							'host'		=> $formValues['host'],
							'username'	=> $formValues['username'],
							'password'	=> $formValues['password'],
							'dbname'	=> $formValues['dbname']
						);

						$dbStatus = $this->_setupDatabase($dbParams);
						if ($dbStatus === true) {
							$this->_saveThemeToDb();
							$isDbReady = true;
						} else {
							unset($this->_session->dbinfo);
							$this->view->messages[] = 'Arrrh! Can\'t install database.<br /><code>'.$dbStatus.'</code>';
						}
					} else {
						$configForm->populate($this->_session->dbinfo['params']);
					}
					
				} else {
					$this->view->messages[] = 'Please, fill all required fields';
					if ($configForm->getElement('corepath')->hasErrors()){
						$this->view->messages[] = implode('<br />', $configForm->getMessages('corepath'));
					}
					if ($configForm->getElement('sitename')->hasErrors()){
	                    $this->view->messages[] = 'Site name should not contain spaces or special characters';
                    }
				}
		}

		if ($isDbReady && $isCoreValid)	{
			$this->_session->nextStep = 3;
			return $this->forward('step3');
		}

		if ($isCoreValid) {
			foreach ($configForm->getDisplayGroup('coreinfo')->getElements() as $element){
				$element->setAttrib('readonly', 'readonly');
			}
		}

		if ($isDbReady) {
			foreach ($configForm->getDisplayGroup('dbinfo')->getElements() as $element){
				$element->setAttrib('readonly', 'readonly');
			}
		}

		$this->view->configform = $configForm;
	}
	
	public function step3Action() {
		$this->_session->nextStep = 3;

		$settingsForm = new Installer_Form_Settings();

		if ($this->getRequest()->isPost()){
			if (!isset($this->_session->configsSaved) || $this->_session->configsSaved !== true) {
				$uri = explode('/', $_SERVER['REQUEST_URI']);
	            array_splice($uri, -2, 2);
				$uri = implode('/', $uri);
				$this->_session->websiteUrl = $_SERVER['HTTP_HOST'].$uri.'/';
				$this->_session->configsSaved = $this->_saveConfigToFs();
			}


			$params = $this->getRequest()->getParams();

			if (isset($params['check']) && $params['check'] === 'settings'){
				if ($settingsForm->isValid($params)){
					$suReady		= $this->_createSuperUser($settingsForm->getValues());

					if (!$settingsForm->getValue('sambaToken') && (bool)$settingsForm->getValue('createAccount')){
						$this->_createSambaAccount($settingsForm->getValues());
					}

					if ($suReady && $this->_session->configsSaved === true) {
						$this->getRequest()->clearParams();
						return $this->forward('tada');
					}
				} else {
					$this->view->messages = $settingsForm->getMessages();
				}
			}
		}
		$this->view->configsSaved = $this->_session->configsSaved;
		$this->view->websiteUrl = $this->_session->websiteUrl;
		
		$this->view->settingsForm = $settingsForm;
	}
	
	public function tadaAction(){
		$this->view->websiteUrl            = $this->_session->websiteUrl;
		$this->view->protocol              = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_SCHEME);
        $this->view->htaccessWritable      = false;
        $this->view->htaccessExists        = false ;
        $this->view->serverConfigGenerated = false;

        //prepare robots.txt
        $this->_prepareRobotsTxt();

        //detecting server software
        if(isset($_SERVER['SERVER_SOFTWARE'])) {
            if(strpos(strtolower($_SERVER['SERVER_SOFTWARE']), 'apache') !== false) {
                //if apache - try to rewrite the .htaccess
                $htaccess = INSTALL_PATH . DIRECTORY_SEPARATOR . '.htaccess';
                if(!file_exists($htaccess)) {
                    $this->view->serverConfigGenerated = false;
                    $this->view->htaccessExists        = false ;
                }
                if(!is_writable($htaccess)) {
                    $this->view->serverConfigGenerated = false;
                    $this->view->htaccessWritable      = false;
                }
                if(false !== @file_put_contents($htaccess, $this->_generateHtaccessContent())) {
                    $this->view->serverConfigGenerated = true;
                }
            }
        }
	}

	public function preDispatch() {
		//solve action w/o layout if request came with PJAX header
		if (isset($_SERVER['HTTP_X_PJAX'])){
			$this->_helper->layout->disableLayout();
		}
		return parent::preDispatch();
	}

    private function _prepareRobotsTxt() {
        $pattern    = '/Sitemap:.*sitemapindex.xml\n/';
        $file       = INSTALL_PATH.DIRECTORY_SEPARATOR.'robots.txt';
        $content    = file_get_contents($file);
        $websiteUrl = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_SCHEME).'://'.$this->_session->websiteUrl;
        $sitemapXml = 'Sitemap: '.$websiteUrl."sitemapindex.xml\n";
        $content    = (preg_match($pattern, $content)) ? preg_replace($pattern, $sitemapXml, $content) : $sitemapXml.$content;

        return file_put_contents($file, $content);
    }

    private function _generateHtaccessContent() {
        $content   = array();
        $content[] = 'RewriteEngine On';

        //generate RewriteBase
        if(isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI']) {
            $rewriteBase = explode(DIRECTORY_SEPARATOR, $_SERVER['REQUEST_URI']);
            array_splice($rewriteBase, -2, 2);
            $rewriteBase = implode(DIRECTORY_SEPARATOR, $rewriteBase);
            if($rewriteBase) {
                $content[] = 'RewriteBase ' . $rewriteBase . DIRECTORY_SEPARATOR;
            }
        }
        //other part of .htaccess
        $content[] = 'RewriteCond %{REQUEST_FILENAME} -s [OR]';
        $content[] = 'RewriteCond %{REQUEST_FILENAME} -l [OR]';
        $content[] = 'RewriteCond %{REQUEST_FILENAME} -d';
        $content[] = 'RewriteRule ^.*$ - [NC,L]';
        $content[] = 'RewriteRule ^.*$ index.php [NC,L]';
        return implode(PHP_EOL, $content);
    }

	/**
	 * Create super user for fresh toaster
	 * @param array $data
	 */
	private function _createSuperUser($data){
		$db = Zend_Db::factory( new Zend_Config($this->_session->dbinfo));
		Zend_Db_Table_Abstract::setDefaultAdapter($db);
		
		$user = array(
			'email'		=> $data['adminEmail'],
			'password'	=> md5($data['adminPassword']),
			'role_id'	=> 'superadmin',
			'full_name'	=> $data['adminName'],
			'reg_date'  => date('Y-m-d h:i:s')
		);
		
		$userTable = new Zend_Db_Table('user');
		$user = $userTable->createRow($user);
		
		if (!$user->save()){
			error_log('Cannot create superadmin');
			return false;
		} else {
			//saving superadmin email to 'config' table
			$settingsTable = new Zend_Db_Table('config');
			$rowset = $settingsTable->find('adminEmail');
			if (null === ($email = $rowset->current())){
				$email = $settingsTable->createRow();
				$email->name	= 'adminEmail';
			}
			$email->value = $user['email'];

			if (!$email->save()){
				error_log('Cannot save adminEmail to config table.');
			}

			if (isset($data['sambaToken']) && !empty($data['sambaToken'])){
				$settingsTable->insert(array(
					'name'  => 'sambaToken',
					'value' => $data['sambaToken']
				));
			}
		}
		
		return true;
	}
	
	public function _saveConfigToFs() {
		if (empty($this->_session->coreinfo['corepath'])){
			$corepath = realpath(INSTALL_PATH .DIRECTORY_SEPARATOR. 'seotoaster_core');
		} else {
			$corepath = realpath($this->_session->coreinfo['corepath']);
		}
		$configPath = realpath($corepath . DIRECTORY_SEPARATOR . $this->_requirements['corePermissions']['configdir']);

        $sitename = !empty ($this->_session->coreinfo['sitename']) ? $this->_session->coreinfo['sitename'] : 'application';

		if (is_file($configPath.DIRECTORY_SEPARATOR.$sitename.'.ini')){
			$sitename = $sitename.'_'.date('Ymdhi');
		}
		//saving coreinfo.php
		try {
			$data = "<?php" . PHP_EOL .
					"define ('CORE', '$corepath/');" . PHP_EOL .
					"define ('SITE_NAME', '$sitename');" . PHP_EOL ;
			file_put_contents(INSTALL_PATH.DIRECTORY_SEPARATOR.$this->_requirements['permissions']['file']['coreinfo'], $data);
			unset($data);
		} catch (Exception $e) {
			error_log($e->getMessage());
			error_log($e->getTraceAsString());
		}

		$routesxml = !empty ($this->_session->coreinfo['sitename']) ? $this->_session->coreinfo['sitename'].'.xml' : 'routes.xml';
		copy(APPLICATION_PATH.'/resourses/routes.xml.default', $configPath.DIRECTORY_SEPARATOR.$sitename.'.routes.xml' );

		$iniPath = $configPath . DIRECTORY_SEPARATOR . $sitename . '.ini';

		//initializing template of application.ini 
		$appIni = file_get_contents(APPLICATION_PATH.'/resourses/application.ini.default');

		$replacements = array(
			'{directory_separator}' => DIRECTORY_SEPARATOR,
			'{websiteurl}'      => $this->_session->websiteUrl,
			'{websitepath}'     => INSTALL_PATH . DIRECTORY_SEPARATOR,
            //force change directory separator in the config (to avoid problems on windows)
//            '{websitepath}'     => str_replace('\\', '/', INSTALL_PATH) . '/',
			'{adapter}'         => $this->_session->dbinfo['adapter'],
			'{host}'            => $this->_session->dbinfo['params']['host'],
			'{username}'        => $this->_session->dbinfo['params']['username'],
			'{password}'        => $this->_session->dbinfo['params']['password'],
			'{dbname}'          => $this->_session->dbinfo['params']['dbname']
		);

		$appIni = strtr($appIni, $replacements);

		//saving application.ini
		try {
			return (bool) file_put_contents($iniPath, $appIni);
		} catch (Exception $e){
			error_log($e->getMessage());
			error_log($e->getTraceAsString());
		}
		
		return false;
	}
	
	/**
	 * Method install database structure from sql file
	 * @param array $dbinfo Array with database settings to be checked
	 * @return mixed true on success, error message on fault
	 */
	private function _setupDatabase($dbinfo){
		$adapter = array('params' => $dbinfo);
		if (extension_loaded('pdo_mysql')) {
			$adapter['adapter'] = 'pdo_mysql';
			$adapter['params']['driver_options'] = array(
				PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES UTF8;'
			);
		} else {
			return "You should have pdo_mysql extension installed";
		}

		try {
			$db = Zend_Db::factory(new Zend_Config($adapter));
			$this->_session->dbinfo = $adapter;
			Zend_Db_Table::setDefaultAdapter($db);

			$file = file_get_contents(APPLICATION_PATH.'/resourses/seotoaster.sql');
			if ($file === false){
				return false;
			}
			$queries = SqlSplitter::split($file);

			$db->beginTransaction();

			try {
				foreach ($queries as $sql) {
					$db->query($sql);
				}
				
				$db->commit();
				return true;
			} catch (Exception $ex) {
				$db->rollBack();
				return $ex->getMessage();
			}

		} catch (Exception $e){
			return $e->getMessage();
		}
	}
	
	private function _findLanguages() {
        $translate = Zend_Registry::get('Zend_Translate');
		$availLanguages = $translate->getAdapter()->getList();
		$flags = scandir(INSTALL_PATH.DIRECTORY_SEPARATOR.'system/images/flags/');

        foreach ($flags as $flag) {
            if (!is_file(INSTALL_PATH.DIRECTORY_SEPARATOR.'system/images/flags/'.$flag)){
                continue;
            }
			$locale = new Zend_Locale(Zend_Locale::getLocaleToTerritory(substr($flag, 0, 2)));
			$lang = $locale->getLanguage();
            if (array_key_exists($lang, $availLanguages)){
                $availLanguages[$lang] = 'system/images/flags/'.$flag;
            }
            unset($locale, $lang, $flag);
		}

		return $availLanguages;
	}

	private function _saveThemeToDb() {
		$templates = glob(INSTALL_PATH.DIRECTORY_SEPARATOR.'themes'.DIRECTORY_SEPARATOR.'default'.DIRECTORY_SEPARATOR.'*.html');
		if (empty($templates)) {
			return false;
		}
		$templateTable = new Zend_Db_Table('template');
		foreach ($templates as $template) {
			$name = explode(DIRECTORY_SEPARATOR, $template);
			$name = str_replace('.html', '', end($name));
			$validator = new Zend_Validate_Db_NoRecordExists(array(
				'table' => 'template',
				'field' => 'name'
			));
			$tmplRow = $templateTable->find($name);
			if ($tmplRow->count()){
				$tmplRow = $tmplRow->current();
				$tmplRow->content = file_get_contents($template);
				$tmplRow->save();
			} else {
				$templateTable->insert(array(
					'name' => $name,
					'content' => file_get_contents($template),
					'type'  => 'typeregular'
				));
			}
            unset($name);
		}

		return true;
	}

	private function _createSambaAccount($userdata){
		if (!isset($userdata['adminName']) || !isset($userdata['adminEmail'])){
			return false;
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://mojo.seosamba.com/backend/sambasignup/index/");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);

		$data = array(
			'email'     => $userdata['adminEmail'],
			'fullName'  => $userdata['adminName'],
			'password'  => substr(md5(uniqid(rand(), true)).sha1(strrev($userdata['adminPassword'])), 4, 10),
			'language' => 'us'
		);

		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

		$result = curl_exec($ch);
//		$info = curl_getinfo($ch);
		curl_close($ch);

		return $result;
	}
}