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
			$this->_redirect('');
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
			    return $this->_forward('step'.$this->_session->nextStep);
			}
		}
		
		$this->_forward('step1');
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
		
		//checking if required libraries are installed
		foreach ($this->_requirements['phpExtensions'] as $name) {
			$phpRequirements[$name] = extension_loaded($name);
		}
		
		//checking if folders has good permissions
		foreach ($this->_requirements['permissions']['dir'] as $dirname) {
			$dirpath = INSTALL_PATH . DIRECTORY_SEPARATOR . $dirname;
			
			if (!is_dir($dirpath)){
				try {
					if (@mkdir($dirpath)){
						$permissions['dir'][$dirname] = true;
					} else {
						$permissions['dir'][$dirname] = 'doesn\'t exist';
						$permissionsFail = true;
					}
				} catch (Exception $e) {
					error_log($e->getMessage());
				}
			} else {
				if (is_writable($dirpath)){
					$permissions['dir'][$dirname] = true;
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
				$permissions['file'][$filename] = true;
			}
		}
		
		
		if (!in_array(false, $phpRequirements) && !$permissionsFail) {
			$this->_session->nextStep = 2;
			$this->view->gotoNext = true;
		} else {
			$this->view->gotoNext = false;
		}
		$this->view->failedPermissions	= $permissionsFail;
		$this->view->permissions		= $permissions;
		$this->view->checks				= $phpRequirements;	
	}
	
	public function step2Action(){
		$configForm = new Installer_Form_Config();
				
		$this->view->gotoNext = false;
		$this->view->messages = array('core' => '', 'db' => null);
		
		$isDbReady	 = false;
		$isCoreReady = false;
		
		// populating data from sessions if exists

        //default values
        $this->_session->coreinfo = array( 'corepath' => INSTALL_PATH . '/seotoaster_core/', 'sitename' => '' );

		if (isset($this->_session->coreinfo)) {
			$configForm->populate($this->_session->coreinfo);
		} else {
			$this->_session->coreinfo = array( 'corepath' => '', 'sitename' => '' );
		}
		if (isset($this->_session->dbinfo) && !empty($this->_session->dbinfo['params'])) {
			$configForm->populate($this->_session->dbinfo['params']);
			$isDbReady = true;
			$this->view->messages['db'] = true;
		}
		
		$params = $this->getRequest()->getParams();
		
		if (isset($params['check']) && $params['check'] === 'config'){
			
			$this->_session->nextStep = 2;

				if (true === ($formValid = $configForm->isValid($params))){
					$formValues = $configForm->getValues();
					
					if (isset($params['corepath']) && isset($params['sitename'])) {
						$coreinfo = array(
							'corepath'	=> $formValues['corepath'],
							'sitename'	=> $formValues['sitename']
							);

						if ($this->_session->coreinfo !== $coreinfo){
							unset($this->_session->coreinfo);
							//checking for core in given path
							if ($coreinfo['corepath'] === ''){
								$corepath = realpath(INSTALL_PATH.'/seotoaster_core/');
							} else {
								$corepath = realpath($coreinfo['corepath']);
							}

							if ( !$corepath || !is_dir($corepath) 
								 || !is_dir($corepath.'/application')
								 || !is_dir($corepath.'/library') ) {
								$this->view->messages['core'] = 'SEOTOASTER Core not found in <code>'.$coreinfo['corepath'].'</code>';
							} else {
								$this->_session->coreinfo = $coreinfo;
							}
						}
					} else {
						$configForm->populate($this->_session->coreinfo);
					}
					
					if (!$isDbReady) {
						$dbinfo = Zend_Registry::get('database');
						$dbinfo['params'] = array(
							'host'		=> $formValues['host'],
							'username'	=> $formValues['username'],
							'password'	=> $formValues['password'],
							'dbname'	=> $formValues['dbname']
						);

						unset($this->_session->dbinfo);
						$dbStatus = $this->_setupDatabase($dbinfo);
						if ($dbStatus === true) {
							$isDbReady = true;
							$this->_session->dbinfo = $dbinfo;
							$this->view->messages['db'] = true;
						} else {
							$this->view->messages['db'] = $dbStatus;
						}
					} else {
						$configForm->populate($this->_session->dbinfo['params']);
					}
					
				} else {
                    if (!$configForm->getElement('sitename')->isValid($params['sitename'])){
                        $this->view->messages['core'] = 'Site name should not contain spaces or special characters';
                    }
				}
			//checking if it is possible to write config files into given core folder
			if (isset($this->_session->coreinfo)){
								
				if ($this->_session->coreinfo['corepath'] === ''){
					$configsDir = realpath(INSTALL_PATH.'/seotoaster_core/').DIRECTORY_SEPARATOR.$this->_requirements['corePermissions']['configdir'];
				} else {
					$configsDir = realpath($this->_session->coreinfo['corepath']).DIRECTORY_SEPARATOR.$this->_requirements['corePermissions']['configdir'];				
				}

				if (is_dir($configsDir)){
					if (!is_writable($configsDir)){
						$coreErrorMessages[] = 'Configs dir must be writable: '.$configsDir;
					}
					$isCoreReady = false;
					$appini = $configsDir . ($this->_session->coreinfo['sitename'] === '' ? $this->_requirements['corePermissions']['appini'] : $this->_session->coreinfo['sitename'].'.ini');
					if (!file_exists($appini)){
						if (!touch($appini)){
							$coreErrorMessages[] = 'File not exists:<br/>'.$appini;
						}
					} else {
						if (!is_writable($appini)){
							$coreErrorMessages[] = 'This file must be writable:<br/>'.$appini;
						}
					}

                    if (!isset($coreErrorMessages) || empty($coreErrorMessages)) {
						$isCoreReady = true;
					} else {
	                    $this->view->messages['core'] = implode('<br />', $coreErrorMessages);
                    }
				} 
			}
			
		}

		if ($isCoreReady) {
			foreach ($configForm->getDisplayGroup('coreinfo')->getElements() as $element){
				$element->setAttrib('readonly', 'readonly');
			}
		}

		if ($isDbReady) {
			foreach ($configForm->getDisplayGroup('dbinfo')->getElements() as $element){
				$element->setAttrib('readonly', 'readonly');
			}
		}
		
		if ($isDbReady && $isCoreReady)	{
			$configForm->removeElement('submit');
			
			$this->view->gotoNext = true;
			$this->_session->nextStep = 3;
		}
		
		$this->view->configform = $configForm;
	}
	
	public function step3Action() { 
		$settingsForm = new Installer_Form_Settings();
		
		if (!isset($this->_session->configsSaved) || $this->_session->configsSaved !== true) {
			$url = parse_url($_SERVER['HTTP_REFERER']);
			$instFolderName = preg_replace('~^.*/([^/]*)$~', '$1', INSTALLER_PATH);
			$this->_session->websiteUrl = $url['host'] .  preg_replace('~^(.*/)'.$instFolderName.'/?(index.php)?$~i', '$1', $url['path']);
			$this->_session->configsSaved = $this->_saveConfigToFs();
		}
		
		$params = $this->getRequest()->getParams();
		
		if (isset($params['check']) && $params['check'] === 'settings' && $settingsForm->isValid($params)){
			$suReady		= $this->_createSuperUser($settingsForm->getValues());
						
			if ($suReady && $this->_session->configsSaved === true) {
				$this->getRequest()->clearParams();
				$this->_forward('tada');
			}
			
		} else {
			$settingsForm->processErrors();
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
	 * @param array $settings 
	 */
	private function _createSuperUser($settings){		
		$adapter = new Zend_Config($this->_session->dbinfo);
		$db = Zend_Db::factory($adapter);
		Zend_Db_Table_Abstract::setDefaultAdapter($db);
		
		$user = array(
			'email'		=> $settings['adminEmail'],
			'password'	=> md5($settings['adminPassword']),
			'role_id'	=> 'superadmin',
			'full_name'	=> 'Administrator',
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
			if ($email = $rowset->current()){
				$email->value = $user['email'];
			} else {
				$email = $settingsTable->createRow();
				$email->name	= 'adminEmail';
				$email->value = $user['email'];
			}

			if (!$email->save()){
				$noErrors = false;
				error_log($email->toArray());
			}
			
		}
		
		return true;
	}
	
	public function _saveConfigToFs() {
		if ($this->_session->coreinfo['corepath'] === ''){
			$corepath = realpath(INSTALL_PATH .DIRECTORY_SEPARATOR. 'seotoaster_core');
			$configPath = realpath($corepath . DIRECTORY_SEPARATOR . $this->_requirements['corePermissions']['configdir']);
		} else {
			$corepath = realpath($this->_session->coreinfo['corepath']);
			$configPath = realpath($this->_session->coreinfo['corepath'] . DIRECTORY_SEPARATOR . $this->_requirements['corePermissions']['configdir']);
		}

        $sitename = !empty ($this->_session->coreinfo['sitename']) ? $this->_session->coreinfo['sitename'] : 'application';
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
		
		$iniPath = ($this->_session->coreinfo['sitename'] === ''
                ? $configPath . DIRECTORY_SEPARATOR . 'application'
                : $configPath . DIRECTORY_SEPARATOR . $this->_session->coreinfo['sitename'] ) . '.ini';

		//initializing template of application.ini 
		$appIni = file_get_contents(INSTALLER_PATH.'/resourses/application.ini.default');
		
		foreach ($this->_session->dbinfo['params'] as $name => $value) {
			$appIni = str_replace('{'.$name.'}', $value, $appIni);
		}
		$appIni = str_replace(
			array('{websiteurl}' , '{websitepath}'),
			array(
				$this->_session->websiteUrl,
				INSTALL_PATH.DIRECTORY_SEPARATOR
				),
			$appIni);
		
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
		
		$adapter = new Zend_Config($dbinfo);
		
		try {
			$db = Zend_Db::factory($adapter);
			$file = file_get_contents(INSTALLER_PATH.'/resourses/seotoaster.sql');
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
}