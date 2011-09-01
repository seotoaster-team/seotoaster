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
		if (!$this->getRequest()->isPost()) {
			if (Zend_Session::sessionExists()){
				$namespace = $this->_session->getNamespace();
				if (isset($_SESSION[$namespace])) {
					unset ($_SESSION[$namespace]);
				}
				Zend_Session::regenerateId();
			}
		} else {
			if ($this->_session->nextStep !== null) {
			    return $this->_forward('step'.$this->_session->nextStep);
			}
		}
		
		$this->_forward('step1');
	}
	
	public function step1Action() {
		$this->_session->nextStep = 1;
		
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
						$permissions['dir'][$dirname] = 'doesn\'t exists';
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
		$this->view->messages = array('core' => array(), 'db' => null);
		
		$isDbReady	 = false;
		$isCoreReady = false;
		
		// populating data from sessions if exists
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

			
				if ($configForm->isValid($params)){
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
								array_push($this->view->messages['core'], 'SEOTOASTER Core not found in '.$coreinfo['corepath']);
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
					$coreForm->processErrors();
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
						array_push($this->view->messages['core'], 'Configs dir must be writable: '.$configsDir);
					} elseif ($this->_session->coreinfo['sitename'] === '' && !is_writable($configsDir . $this->_requirements['corePermissions']['appini'])) {
						array_push($this->view->messages['core'], 'This files in <b>core</b> folder must be writable: <br />'.$this->_requirements['corePermissions']['configdir']. $this->_requirements['corePermissions']['appini']);
					} else {
						$isCoreReady = true;
					}
				} 
			}
			
		}

		if ($isCoreReady) {
			foreach ($configForm->getDisplayGroup('coreinfo')->getElements() as $element){
				$element->setAttrib('disabled', 'disabled');
			}
		}
		
		if ($isDbReady) {
			foreach ($configForm->getDisplayGroup('dbinfo')->getElements() as $element){
				$element->setAttrib('disabled', 'disabled');
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
		
		if (!isset($this->_session->configsSaved) || $this->_session->configsSaved != true) {
			$url = parse_url($_SERVER['HTTP_REFERER']);
			$instFolderName = preg_replace('~^.*/([^/]*)$~', '$1', INSTALLER_PATH);
			$this->_session->websiteUrl = $url['host'] .  preg_replace('~^(.*/)'.$instFolderName.'/?(index.php)?$~i', '$1', $url['path']);
			$result = $this->_saveConfigToFs();
			$this->_session->configsSaved = $result;
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
		
		$this->view->settingsForm = $settingsForm;
	}
	
	public function tadaAction(){
		$this->view->websiteUrl = $this->_session->websiteUrl;
		$this->view->protocol = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_SCHEME);
	}

	public function preDispatch() {
		//solve action w/o layout if request came with PJAX header
		if (isset($_SERVER['HTTP_X_PJAX'])){
			$this->_helper->layout->disableLayout();
		}
		return parent::preDispatch();
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
		
		//saving coreinfo.php
		try {
			$data = "<?php" . PHP_EOL .
					"define ('CORE', '".$corepath."/');" . PHP_EOL .
					"define ('SITE_NAME', '". $this->_session->coreinfo['sitename'] ."/');" . PHP_EOL ;
			file_put_contents(INSTALL_PATH.DIRECTORY_SEPARATOR.$this->_requirements['permissions']['file']['coreinfo'], $data);
			unset($data);
		} catch (Exception $e) {
			error_log($e->getMessage());
			error_log($e->getTraceAsString());
		}
		
		$savePath = $this->_session->coreinfo['sitename'] === '' ? $configPath : $configPath . DIRECTORY_SEPARATOR . $this->_session->coreinfo['sitename'];
		
		if (!is_dir($savePath)){
			mkdir($savePath);
		}
		
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
		
		//saving application.ini and copying routes.xml files to specified core folder
		try {
			if (
				file_put_contents($savePath.DIRECTORY_SEPARATOR.$this->_requirements['corePermissions']['appini'], $appIni) &&
				copy(INSTALLER_PATH.'/resourses/routes.xml.default', $savePath.DIRECTORY_SEPARATOR.'routes.xml')
			) {
				return true;
			} 
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
}