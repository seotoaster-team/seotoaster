<?php

/**
 * UpdateController - handler for upadate
 *
 * @author Vitaly Vyrodov <vitaly.vyrodov@gmail.com>
 */
class Backend_UpdateController extends Zend_Controller_Action
{
    const MASTER_CMS_LINK = 'http://seotoaster.com/cms.txt';
    const MASTER_STORE_LINK = 'http://seotoaster.com/store.txt';
    const WHATISNEW_CMS_LINK = 'http://seotoaster.com/cms-changelog.md';
    const WHATISNEW_STORE_LINK = 'http://seotoaster.com/store-changelog.md';
    const MASTER_CRM_LINK = 'http://seotoaster.com/crm.txt';
    const WHATISNEW_CRM_LINK = 'http://seotoaster.com/crm-changelog.md';

    const BACKUP_NAME = 'backup.zip';
    const PACK_NAME = 'toaster.zip';

    //new misc.jqversion in application.ini
    const JQVERSION = '3.5.1';

    //new misc.jquversion in application.ini
    const JQUVERSION = '1.12.1';

    protected $_redirector;
    protected $_session;
    protected $_downloadLink;
    protected $_toasterVersion;
    protected $_storeVersion;
    protected $_crmVersion;
    protected $_remoteVersion;
    protected $_websitePath;
    protected $_tmpPath;
    protected $_newToasterPath;
    protected $_logFile;
    protected $_whatIsNew = array();


    /**
     * Init method. Checking permissions.
     */
    public function init()
    {
        parent::init();
        $this->_redirector = new Zend_Controller_Action_Helper_Redirector();
        $this->_session = Zend_Controller_Action_HelperBroker::getStaticHelper('session');
        $this->view->websiteUrl = $this->_helper->website->getUrl();
        // Checks, is it enough permissions for update
        if ($this->_session->getCurrentUser()->getRoleId(
            ) !== Tools_Security_Acl::ROLE_SUPERADMIN && $this->_session->getCurrentUser()->getRoleId(
            ) !== Tools_Security_Acl::ROLE_ADMIN
        ) {
            $this->_redirector->gotoUrlAndExit($this->_helper->website->getUrl());
        }

        $this->_websitePath = $this->_helper->website->getPath();
        $this->_tmpPath = $this->_helper->website->getTmp();
        $this->_newToasterPath = $this->_helper->website->getPath() . $this->_helper->website->getTmp(
            ) . 'updates' . DIRECTORY_SEPARATOR;
        $this->view->helpSection = 'updater';
        $this->_session->withoutBackup = $this->_request->getParam('withoutBackup') === 'true' ? true : false;

        try {
            if (file_exists($this->_websitePath . 'plugins/shopping/version.txt') && !file_exists($this->_websitePath . 'plugins/leads/version.txt')) {
                $this->_storeVersion = $this->_getFileContent($this->_websitePath . 'plugins/shopping/version.txt');
                $whatIsNew = file_get_contents(self::WHATISNEW_STORE_LINK);
                $master_link = self::MASTER_STORE_LINK;
            } elseif (file_exists($this->_websitePath . 'plugins/leads/version.txt')) {
                $this->_crmVersion = $this->_getFileContent($this->_websitePath . 'plugins/leads/version.txt');
                $whatIsNew = file_get_contents(self::WHATISNEW_CRM_LINK);
                $master_link = self::MASTER_CRM_LINK;
            } else {
                $whatIsNew = file_get_contents(self::WHATISNEW_CMS_LINK);
                $master_link = self::MASTER_CMS_LINK;
            }
            $this->_toasterVersion = $this->_getFileContent('version.txt');

            $master_versions = explode("\n", file_get_contents($master_link));
            $this->_remoteVersion = filter_var($master_versions [0], FILTER_SANITIZE_STRING);
            $this->_downloadLink = filter_var($master_versions [1], FILTER_SANITIZE_URL);
            $this->_whatIsNew = explode("\n", stristr(trim($whatIsNew), 'Version: ' . $this->_remoteVersion));
            array_shift($this->_whatIsNew);
        } catch (Exceptions_SeotoasterException $se) {
            if (self::debugMode()) {
                error_log($se->getMessage());
                return $this->view->result = $this->_helper->language->translate('Can\'t get toasters version');
            }
        }
    }

    public function versionAction() {
        $version = $this->_storeVersion ? $this->_storeVersion : $this->_toasterVersion;
        $updateStatus = version_compare($this->_remoteVersion, $version);
        if (1 === $updateStatus) {
            $this->_response('success', 1, $this->_remoteVersion);
        }
        $this->_response('success', 0, '');
    }

    public function indexAction()
    {
        $this->view->remoteVersion = $this->_remoteVersion;
        if (count($this->_whatIsNew)) {
            $this->view->whatIsNew = $this->_whatIsNew;
        }

        $this->view->localVersion = $this->_toasterVersion;
        /*if ($this->_storeVersion) {
            $this->view->localVersion = $this->_storeVersion;
        } elseif ($this->_crmVersion) {
            $this->view->localVersion = $this->_crmVersion;
        } else {
            $this->view->localVersion = $this->_toasterVersion;
        }*/
        if (!$this->_session->nextStep) {
            $this->_session->nextStep = 1;
        }

        $appIni = CORE . 'application/configs/' . SITE_NAME . '.ini';

        $updateconfigFlag = false;
        $jqversion = '';
        $jquversion = '';
        $attentionMessage = '';
        if (is_file($appIni)) {
            $appIniArray = parse_ini_file($appIni);

            if(!empty($appIniArray)) {
                if($appIniArray['misc.jqversion'] < self::JQVERSION || $appIniArray['misc.jquversion'] < self::JQUVERSION) {
                    $updateconfigFlag = true;
                    $jqversion = $appIniArray['misc.jqversion'];
                    $jquversion = $appIniArray['misc.jquversion'];
                    $attentionMessage = $this->_helper->language->translate('jQuery version is outdated');
                }
            }
        }

        $this->view->updateconfigFlag = $updateconfigFlag;
        $this->view->jqversion  = $jqversion;
        $this->view->jquversion  = $jquversion;

        $this->view->attentionMessage  = $attentionMessage;
    }

    /**
     * The main method of updating
     * @return mixed
     */
    public function updateAction()
    {
        ini_set('memory_limit','400M');

        /**
         * Step 1: Checks the current version of the toaster. And if needs updating puts NextStep = 2
         */

        $version = $this->_toasterVersion;

        /*if ($this->_storeVersion) {
            $version = $this->_storeVersion;
        } elseif ($this->_crmVersion) {
            $version = $this->_crmVersion;
        } else {
            $version = $this->_toasterVersion;
        }*/

        if ($this->_session->nextStep === 1) {
            $updateStatus = version_compare($this->_remoteVersion, $version);
            if (1 === $updateStatus) {
                $this->_session->nextStep = 2;
                if ($this->_session->withoutBackup === false) {
                    return $this->_response('success', 1, 'Update started. Creating backup.');
                } else {
                    return $this->_response('success', 1, 'Update started. Please wait.');
                }
            } elseif (-1 === $updateStatus) {
                return $this->_response('success', 0, 'Your version of the system is higher than the remote.');
            } else {
                return $this->_response('success', 0, 'Your system up to date.');
            }
        }

        /**
         *  Step 2: Creates a backup of the toaster. And puts NextStep = 3
         */
        if ($this->_session->nextStep === 2) {
            if ($this->_session->withoutBackup === false) {
                $oldBackup = array_shift(glob($this->_tmpPath . '*-' . self::BACKUP_NAME));
                if ($oldBackup) {
                    unlink($oldBackup);
                }
                $result = $this->_zipUnzip(
                    'compress',
                    $this->_websitePath,
                    $this->_tmpPath,
                    time() . '-' . self::BACKUP_NAME
                );
                if (isset($result) && $result === true) {
                    $this->_session->nextStep = 3;
                    return $this->_response('success', 1, 'Backup created. Path to backup: "' .
                        array_shift(glob($this->_tmpPath . '*-' . self::BACKUP_NAME)) . ' Downloading started.');
                } else {
                    return $this->_response('fail', 0, 'Can\'t create toaster backup.');
                }
            } else {
                $this->_session->nextStep = 3;
                return $this->_response('success', 1, 'Without backup. Downloading started.');
            }
        }

        /**
         *  Step 3: Loading Pack. And puts NextStep = 4
         */
        if ($this->_session->nextStep === 3) {
            $result = $this->_getZip(self::PACK_NAME, $this->_tmpPath, $this->_newToasterPath);
            if (isset($result) && $result === true) {
                $this->_session->nextStep = 4;
                return $this->_response('success', 1, 'Toaster zip downloaded. Unzipping.');
            } else {
                unlink($this->_tmpPath . self::PACK_NAME);
                return $this->_response('fail', 0, 'Can\'t download zip.');
            }
        }

        /**
         *  Step 4: Unzipping Pack. And puts NextStep = 5
         */
        if ($this->_session->nextStep === 4) {
            $result = $this->_zipUnzip('decompress', $this->_tmpPath, $this->_newToasterPath, self::PACK_NAME);
            if (isset($result) && $result === true) {
                $this->_session->nextStep = 5;
                return $this->_response('success', 1, 'Toaster unzipped. Copying files.');
            } else {
                return $this->_response('fail', 0, 'Can\'t unzip toaster.');
            }
        }

        /**
         *  Step 5: Altering DataBase. And puts NextStep = 6
         */
        if ($this->_session->nextStep === 5) {
            $result = $this->_updateDataBase();
            if (isset($result) && $result === true) {
                $this->_session->nextStep = 6;
                return $this->_response('success', 1, 'Database altered.');
            } else {
                return $this->_response('fail', 0, 'Unsuccessful attempt to altering database.');
            }
        }

        /**
         *  Step 6: Replace the files. And puts NextStep = 7
         */
        if ($this->_session->nextStep === 6) {
            $result = $this->_copyToaster($this->_newToasterPath, $this->_websitePath);
            if (isset($result) && $result === true) {
                $this->_cleanCache();
                $this->_session->nextStep = 7;
                return $this->_response('success', 1, 'Toaster files copied.');
            } else {
                if ($this->_session->withoutBackup === false) {
                    $this->_zipUnzip(
                        'decompress',
                        $this->_tmpPath,
                        $this->_websitePath,
                        array_shift(glob($this->_tmpPath . '*-' . self::BACKUP_NAME))
                    );
                    return $this->_response('fail', 0, 'Unsuccessful attempt to copy files. The old version of the files is restored.');
                } else {
                    return $this->_response('fail', 0, 'Unsuccessful attempt to copy files.');
                }
            }
        }

        /**
         *  Step 7: Completing update. And puts NextStep = 1
         */
        if ($this->_session->nextStep === 7) {
            $this->_session->nextStep = 1;
            /** Can be removed for version of the packages higher than 2.5.0 */
            $this->_cleanCache();
            return $this->_response('success', 0, 'Success!');
        }
    }

    /**
     * Update old mobile phone format
     *
     * @return mixed
     * @throws Exceptions_SeotoasterException
     */
    public function updatemobilephonenumbersAction()
    {
        $userMapper = Application_Model_Mappers_UserMapper::getInstance();
        $where = $userMapper->getDbTable()->getAdapter()->quoteInto('ua.attribute = ?', 'mobilecountrycode');
        $where .= ' AND '. $userMapper->getDbTable()->getAdapter()->quoteInto('ua.attribute <> ?', '');
        $where .= ' AND '. $userMapper->getDbTable()->getAdapter()->quoteInto('u.mobile_phone LIKE ?', '%+%');
        $select = $userMapper->getDbTable()->getAdapter()->select()->from(array('u' => 'user'), array('u.id', 'u.mobile_phone', 'oldMobileCountryCode' => 'ua.value'))
            ->join(array('ua' => 'user_attributes'), 'u.id=ua.user_id', array())
            ->where($where)
            ->limit(1);

        $usersToProcess = $userMapper->getDbTable()->getAdapter()->fetchAll($select);
        if (!empty($usersToProcess)) {
            foreach ($usersToProcess as $userToProcess) {
                $userModel = $userMapper->find($userToProcess['id']);
                $oldMobileCountryCode = $userToProcess['oldMobileCountryCode'];
                $oldMobilePhone = $userToProcess['mobile_phone'];
                if ($userModel instanceof Application_Model_Models_User && !empty($oldMobileCountryCode)) {
                    $mobileCountryPhoneCode = Zend_Locale::getTranslation($oldMobileCountryCode, 'phoneToTerritory');
                    if (!empty($mobileCountryPhoneCode)) {
                        $mobileCountryCodeValue = '+' . $mobileCountryPhoneCode;
                        if (preg_match('~'.preg_quote($mobileCountryCodeValue).'~ui', $oldMobilePhone)) {
                            $mobilePhone = str_replace($mobileCountryCodeValue, '', $oldMobilePhone);
                            $userModel->setMobileCountryCode($oldMobileCountryCode);
                            $userModel->setMobileCountryCodeValue($mobileCountryCodeValue);
                            $userModel->setMobilePhone($mobilePhone);
                            $userModel->setPassword('');
                            $userMapper->save($userModel);

                            $shoppingPlugins = Tools_Plugins_Tools::getPluginsByTags(array('processphones'));
                            $methodName = 'processPhoneCodes';
                            $shoppingPluginsStatus = array();
                            if (!empty($shoppingPlugins)) {
                                foreach ($shoppingPlugins as $pluginName => $shoppingPlugin) {
                                    if ($shoppingPlugin->getStatus() === Application_Model_Models_Plugin::ENABLED) {
                                        $pluginName = ucfirst($shoppingPlugin->getName());
                                        if (class_exists($pluginName) && method_exists($pluginName,
                                                $methodName)
                                        ) {
                                            $reflection = new ReflectionMethod($pluginName, $methodName);
                                            if ($reflection->isPublic()) {
                                                $shoppingPluginsStatus[$pluginName] = true;
                                            }
                                        }
                                    }
                                }

                                if(!empty($shoppingPluginsStatus)){
                                    foreach ($shoppingPluginsStatus as $plugin => $status){
                                        $plugin = Tools_Factory_PluginFactory::createPlugin($plugin, array(),
                                            array());
                                        $plugin->$methodName($userModel);
                                    }
                                }
                            }
                        } else {
                            $whereAttr = $userMapper->getDbTable()->getAdapter()->quoteInto('user_id = ?', $userToProcess['id']);
                            $whereAttr .=  ' AND '.$userMapper->getDbTable()->getAdapter()->quoteInto('attribute = ?', 'mobilecountrycode');
                            $userMapper->getDbTable()->getAdapter()->delete('user_attributes', $whereAttr);
                        }
                    }
                }
            }

            return $this->_helper->response->success(
                array('status' => 'success', 'message' => '', 'processedCount' => count($usersToProcess))
            );
        }

        Application_Model_Mappers_ConfigMapper::getInstance()->save(array('oldMobileFormat' => 1));
        return $this->_helper->response->fail(
            array('status' => 'success', 'message' => $this->_helper->language->translate('New mobile format applied'), 'processedCount' => count($usersToProcess))
        );
    }

    /**
     * @param string $zipName
     * @param string $path
     * @param  string $newPath
     * @return bool
     */
    protected function _getZip($zipName = self::PACK_NAME, $path, $newPath)
    {
        $upZip = $path . $zipName;
        if (!is_dir($newPath)) {
            @mkdir($newPath);
        }
        set_time_limit(0);
        $fp = fopen($upZip, 'w+');
        $ch = curl_init($this->_downloadLink);
        curl_setopt($ch, CURLOPT_TIMEOUT, 100);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
        return true;
    }

    /**
     * Zipping/Unzipping method.
     * @param string $action
     * @param string $source
     * @param string $destination
     * @param string $name
     * @return bool
     */
    protected function _zipUnzip($action, $source, $destination, $name)
    {
        if (!extension_loaded('zip') || !file_exists($source)) {
            return false;
        }
        $zip = new ZipArchive();
        if ($action === 'compress') {
            $filesForBackup = array(
                $source . 'favicon.ico',
                $source . 'feeds',
                $source . '.htaccess',
                $source . 'index.php',
                $source . '_install',
                $source . 'plugins',
                $source . 'robots.txt',
                $source . 'seotoaster_core',
                $source . 'system',
                $source . 'version.txt',
                $source . 'themes'
            );
            if (!$zip->open($destination . $name, ZIPARCHIVE::CREATE)) {
                return false;
            }
            foreach ($filesForBackup as $source) {
                $source = str_replace('\\', '/', realpath($source));
                if (is_dir($source) === true) {
                    $files = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($source),
                        RecursiveIteratorIterator::SELF_FIRST
                    );
                    foreach ($files as $file) {
                        $file = str_replace('\\', '/', realpath($file));
                        if (is_dir($file) === true) {
                            $zip->addEmptyDir(str_replace($this->_websitePath, '', $file . '/'));
                        } else {
                            if (is_file($file) === true) {
                                $zip->addFromString(
                                    str_replace($this->_websitePath, '', $file),
                                    file_get_contents($file)
                                );
                            }
                        }
                    }
                } else {
                    if (is_file($source) === true) {
                        $zip->addFromString(basename($source), file_get_contents($source));
                    }
                }
            }
            $zip->close();
            return true;
        } elseif ($action === 'decompress') {
            $res = $zip->open($source . $name);
            if ($res === true) {
                $zip->extractTo($destination);
                $zip->close();
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Replacing toaster method.
     * @param string $source
     * @param string $dest
     * @return bool
     */
    protected function _copyToaster($source, $dest)
    {
        foreach (
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            ) as $item
        ) {
            if ($item->isDir()) {
                @mkdir($dest . $iterator->getSubPathName());
            } else {
                copy($item, $dest . $iterator->getSubPathName());
                unlink($item->getPathName());
            }
        }
        Tools_Filesystem_Tools::deleteDir($source);
        $routesUpdated = $this->_updateRoutes();
        if (!$routesUpdated) {
            return false;
        }
        return true;
    }

    /**
     * Database altering method
     * @return bool
     */
    protected function _updateDataBase()
    {
        $dbAdapter = Zend_Db_Table::getDefaultAdapter();

        $select = $dbAdapter->select()->from('config', array('value'))->where('name = ?', 'version');
        $dbVersion = $dbAdapter->fetchRow($select);
        $alters = $this->_getFileContent(
            $this->_newToasterPath . '_install/alters.sql',
            '-- version: ' . $dbVersion['value']
        );

        $sqlAlters = Tools_System_SqlSplitter::split($alters);
        $cnt = 0;
        try {
            foreach ($sqlAlters as $alter) {
                $dbAdapter->query($alter);
                $cnt++;
            }
            return true;
        } catch (Exception $ex) {
            error_log($ex->getMessage());
            return false;
        }
    }

    /**
     * @param string $fileNameWithPath
     * @param bool $needle
     * @return string
     */
    protected function _getFileContent($fileNameWithPath, $needle = false)
    {
        if ($needle) {
            return stristr(trim(Tools_Filesystem_Tools::getFile($fileNameWithPath)), $needle);
        } else {
            return trim(Tools_Filesystem_Tools::getFile($fileNameWithPath));
        }
    }

    /**
     * Response helper
     * @param string $type
     * @param int $status
     * @param string $message
     * @return mixed
     */
    protected function _response($type, $status, $message)
    {
        return $this->_helper->response->$type(
            array('status' => $status, 'message' => $this->_helper->language->translate($message))
        );

    }

    protected function _updateRoutes()
    {
        try {
            $routesFile = APPLICATION_PATH . '/configs/' . SITE_NAME . '.routes.xml';
            $defaultRoutesFile = APPLICATION_PATH . '/../../_install/installer/resourses/routes.xml.default';
            if (!is_file($routesFile)) {
                $routesFile = APPLICATION_PATH . '/configs/routes.xml';
            }
            $routes = array();
            if (file_exists($routesFile) && file_exists($defaultRoutesFile)) {
                $routes = new Zend_Config_Xml($routesFile, 'routes');
                $defaultRoutes = new Zend_Config_Xml($defaultRoutesFile, 'routes');
            }
            $routesWriter = new Zend_Config_Writer_Xml();
            $routesWriter->setConfig(new Zend_Config(array('routes' => array_replace($defaultRoutes->toArray(), $routes->toArray()))));
            $routesWriter->write($routesFile);
            return true;
        } catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    protected function _cleanCache()
    {
        unlink($this->_tmpPath . self::PACK_NAME);
        $caches = glob($this->_websitePath . 'cache/zend_cache-*');
        foreach ($caches as $cache) {
            if (is_dir($cache)) {
                Tools_Filesystem_Tools::deleteDir($cache);
            }
            unlink($cache);
        }
        return true;
    }

    /**
     * Update application.ini
     * @return mixed
     */
    public function applywebsiteconfigchangesAction() {
        $appIniPath = CORE . 'application/configs/' . SITE_NAME . '.ini';
        $appIniFile = file_get_contents(CORE . 'application/configs/' . SITE_NAME . '.ini');

        $oldJqversion = $this->_request->getParam('jqversion');
        $oldJquversion = $this->_request->getParam('jquversion');

        if(!empty($oldJqversion) || !empty($oldJquversion)) {

            $replacements = array(
                $oldJqversion   => self::JQVERSION,
                $oldJquversion  => self::JQUVERSION
            );

            $appIniFile = strtr($appIniFile, $replacements);
        }


        if(!empty($oldJqversion) || !empty($oldJquversion)) {
            try {
                file_put_contents($appIniPath, $appIniFile);

                return $this->_response('success', 1, 'Success!');
            } catch (Exception $e){
                error_log($e->getMessage());
                error_log($e->getTraceAsString());
            }
        }

        return $this->_response('fail', 0, 'Can\'t update config.');
    }

}
