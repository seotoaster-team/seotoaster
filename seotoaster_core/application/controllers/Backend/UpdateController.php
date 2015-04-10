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
    const BACKUP_NAME = 'backup.zip';
    const PACK_NAME = 'toaster.zip';

    protected $_redirector;
    protected $_session;
    protected $_downloadLink;
    protected $_toasterVersion;
    protected $_storeVersion;
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
            if (file_exists($this->_websitePath . 'plugins/shopping/version.txt')) {
                $this->_storeVersion = $this->_getFileContent($this->_websitePath . 'plugins/shopping/version.txt');
                $whatIsNew = file_get_contents(self::WHATISNEW_STORE_LINK);
                $master_link = self::MASTER_STORE_LINK;
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

    public function indexAction()
    {
        $this->view->remoteVersion = $this->_remoteVersion;
        if (count($this->_whatIsNew)) {
            $this->view->whatIsNew = $this->_whatIsNew;
        }
        if ($this->_storeVersion) {
            $this->view->localVersion = $this->_storeVersion;
        } else {
            $this->view->localVersion = $this->_toasterVersion;
        }
        if (!$this->_session->nextStep) {
            $this->_session->nextStep = 1;
        }
    }

    /**
     * The main method of updating
     * @return mixed
     */
    public function updateAction()
    {
        /**
         * Step 1: Checks the current version of the toaster. And if needs updating puts NextStep = 2
         */
        $version = $this->_storeVersion ? $this->_storeVersion : $this->_toasterVersion;
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
            unlink($this->_tmpPath . self::PACK_NAME);
            $caches = glob($this->_websitePath . 'cache/zend_cache-*');
            foreach ($caches as $cache) {
                if (is_dir($cache)) {
                    Tools_Filesystem_Tools::deleteDir($cache);
                }
                unlink($cache);
            }
            return $this->_response('success', 0, 'Success!');
        }
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
                $source . 'version.txt'
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
        if ($this->_storeVersion) {
            $select = $dbAdapter->select()->from('shopping_config', array('value'))->where('name = ?', 'version');
            $dbVersion = $dbAdapter->fetchRow($select);
            $storeAlters = $this->_getFileContent(
                $this->_newToasterPath . 'plugins/shopping/system/store-alters.sql',
                '-- version: ' . $dbVersion['value']
            );
            $revertStoreAlters = $this->_getFileContent(
                $this->_newToasterPath . 'plugins/shopping/system/revert-store-alters.sql',
                '-- version: ' . $dbVersion['value']
            );
        }

        $select = $dbAdapter->select()->from('config', array('value'))->where('name = ?', 'version');
        $dbVersion = $dbAdapter->fetchRow($select);
        $alters = $this->_getFileContent(
            $this->_newToasterPath . '_install/alters.sql',
            '-- version: ' . $dbVersion['value']
        );
        $revertAlters = $this->_getFileContent(
            $this->_newToasterPath . '_install/revert-alters.sql',
            '-- version: ' . $dbVersion['value']
        );

        if (!empty($storeAlters)) {
            $alters = $alters . ' ' . $storeAlters;
        }
        if (!empty($revertStoreAlters)) {
            $revertAlters = $revertAlters . ' ' . $revertStoreAlters;
        }

        $sqlAlters = Tools_System_SqlSplitter::split($alters);
        $revertSqlAlters = Tools_System_SqlSplitter::split($revertAlters);
        $cnt = 0;
        try {
            foreach ($sqlAlters as $alter) {
                $dbAdapter->query($alter);
                $cnt++;
            }
            return true;
        } catch (Exception $ex) {
            for ($i = 0; $i < $cnt; $i++) {
                $dbAdapter->query($revertSqlAlters[$i]);
            }
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

}
