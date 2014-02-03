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

    protected $_redirector      = null;
    protected $_session         = null;
    protected $_downloadLink    = null;
    protected $_toasterVersion  = null;
    protected $_remoteVersion   = null;
    protected $_websitePath     = null;
    protected $_tmpPath         = null;
    protected $_newToasterPath  = null;
    protected $_logFile         = null;

    /**
     * Init method. Checking permissions.
     */
    public function init()
    {
        parent::init();
        $this->_redirector = new Zend_Controller_Action_Helper_Redirector();
        $this->_session = Zend_Controller_Action_HelperBroker::getStaticHelper('session');
        $this->view->websiteUrl = $this->_helper->website->getUrl();

        if ($this->_session->getCurrentUser()->getRoleId() !== Tools_Security_Acl::ROLE_SUPERADMIN) {
            $this->_redirector->gotoUrlAndExit($this->_helper->website->getUrl());
        }

        $this->_websitePath = $this->_helper->website->getPath();
        $this->_tmpPath = $this->_helper->website->getTmp();
        $this->_newToasterPath = $this->_helper->website->getPath() . $this->_helper->website->getTmp(
            ) . 'updates' . DIRECTORY_SEPARATOR;
        $this->view->helpSection = 'updater';

        try {
            if (file_exists($this->_websitePath . 'plugins/shopping/version.txt')) {
                $this->_toasterVersion = trim(
                    Tools_Filesystem_Tools::getFile($this->_websitePath . 'plugins/shopping/version.txt')
                );
                $master_link = self::MASTER_STORE_LINK;
            } else {
                $this->_toasterVersion = trim(Tools_Filesystem_Tools::getFile('version.txt'));
                $master_link = self::MASTER_CMS_LINK;
            }
            $master_versions = explode("\n", file_get_contents($master_link));
            $this->_remoteVersion = filter_var($master_versions [0], FILTER_SANITIZE_STRING);
            $this->_downloadLink = filter_var($master_versions [1], FILTER_SANITIZE_URL);
        } catch (Exceptions_SeotoasterException $se) {
            if (self::debugMode()) {
                error_log($se->getMessage());
                return $this->view->result = "Can't get toasters version";
            }
        }
    }

    public function indexAction()
    {
        $this->view->remoteVersion = $this->_remoteVersion;
        $this->view->localVersion = $this->_toasterVersion;
        if (!$this->_session->nextStep) {
            $this->_session->nextStep = 1;
        }
    }


    public function updateAction()
    {
        $this->_session->withoutBackup = $this->_request->getParam('withoutBackup') === 'true' ? true : false;

        if ($this->_session->nextStep === 1) {
            $updateStatus = version_compare($this->_remoteVersion, $this->_toasterVersion);
            if (1 === $updateStatus) {
                $this->_session->nextStep = 2;
                return $this->_helper->response->success($this->_helper->language->translate('Update started!'));
            } elseif (-1 === $updateStatus) {
                return $this->_helper->response->success($this->_helper->language->translate('Your version of the system is higher than the remote'));
            } else {
                return $this->_helper->response->success($this->_helper->language->translate('Your system up to date'));
            }
        }

        if ($this->_session->nextStep === 2) {
            try {
                if ( $this->_session->withoutBackup === false) {
                    $this->_zipUnzip('compress', $this->_websitePath, $this->_tmpPath, 'backup.zip');
                    $this->_session->nextStep = 3;
                    $this->_helper->response->success($this->_helper->language->translate('Backup created'));
                } else {
                    $this->_session->nextStep = 3;
                }
            } catch (Exception $se) {
                error_log($se->getMessage());
                return $this->_helper->response->success($this->_helper->language->translate("Can't create toaster backup!"));
            }

        }

        if ($this->_session->nextStep === 3) {
            try {
                $this->_getZip('toaster.zip', $this->_tmpPath, $this->_newToasterPath);
                $this->_session->nextStep = 4;
                $this->_helper->response->success($this->_helper->language->translate('Toaster zip downloaded!'));
            } catch (Exception $se) {
                error_log($se->getMessage());
                return  $this->_helper->response->success($this->_helper->language->translate("Can't download zip"));
            }
        }
        if ($this->_session->nextStep === 4) {
            try {
                $this->_zipUnzip('decompress', $this->_tmpPath, $this->_newToasterPath, 'toaster.zip');
                $this->_session->nextStep = 5;
                $this->_helper->response->success($this->_helper->language->translate('Toaster unziped!'));
            } catch (Exception $se) {
                error_log($se->getMessage());
                return  $this->_helper->response->success($this->_helper->language->translate("Can't unzip toaster"));
            }
        }

        if ($this->_session->nextStep === 5) {
            try {
                $this->_copyConfigs();
                $this->_copyToaster($this->_newToasterPath . 'install', $this->_newToasterPath . '_install');
                $this->_session->nextStep = 6;
                $this->_helper->response->success($this->_helper->language->translate('Configs copied!'));
            } catch (Exception $se) {
                error_log($se->getMessage());
                return  $this->_helper->response->success($this->_helper->language->translate("Can't copy config files!"));
            }
        }

        if ($this->_session->nextStep === 6) {
            try {
                //$this->_copyToaster($this->_newToasterPath, $this->_websitePath);
                $this->_session->nextStep = 7;
                $this->_helper->response->success($this->_helper->language->translate('Toaster files copied!'));

            } catch (Exception $ex) {
                $this->_zipUnzip('decompress', $this->_tmpPath, $this->_websitePath, 'backup.zip');
                $this->_helper->response->success($this->_helper->language->translate('Unsuccessful attempt to copy files. The old version of the files is restored!'));
                return $ex->getMessage();
            }
        }

        if ($this->_session->nextStep === 7) {
            try {
                //$this->_updateDataBase();
                $this->_session->nextStep = 8;
                $this->_helper->response->success($this->_helper->language->translate('Database altered!'));

            } catch (Exception $ex) {
                $this->_helper->response->success($this->_helper->language->translate('UUnsuccessful attempt to altering database!'));
                return $ex->getMessage();
            }
        }

        if ($this->_session->nextStep === 8) {
            return  $this->_helper->response->success($this->_helper->language->translate("Success"));
        }
    }


    protected function _getZip($zipName = 'toaster.zip', $path, $newPath)
    {
        try {
            $upZip = $path . $zipName;
            //TODO Check if dir exist and not empty
            if (!is_dir($newPath)) {
                mkdir($newPath);
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
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

    protected function _copyConfigs()
    {
        if (!copy($this->_websitePath . 'system/coreinfo.php', $this->_newToasterPath . 'system/coreinfo.php')) {
            return false;
        }
        mkdir($this->newToasterPath . '_install');
        return true;
    }

    protected function _zipUnzip($action, $path, $tmpPath, $zipName)
    {
        if ($action === 'compress') {
            $filter = new Zend_Filter_Compress(
                array(
                    'adapter' => 'Zip',
                    'options' => array(
                        'archive' => $zipName,
                    )
                )
            );
            $filter->filter($path);
            rename($path . $zipName, $tmpPath . $zipName);
            return true;
        } elseif ($action === 'decompress') {
            $filter = new Zend_Filter_Decompress(
                array(
                    'adapter' => 'Zip',
                    'options' => array(
                        'target' => $tmpPath,
                    )
                )
            );
            $filter->filter($path . $zipName);
            return true;
        }
        return false;
    }

    protected function _copyToaster($source, $dest)
    {
        foreach (
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST) as $item
        ) {
            if ($item->isDir()) {
                mkdir($dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
            } else {
                copy($item, $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
                unlink($item->getPathName());
            }
        }
        Tools_Filesystem_Tools::deleteDir($source);
        return true;
    }

    protected function _updateDataBase()
    {
        $dbAdapter = Zend_Db_Table::getDefaultAdapter();
        if (file_exists($this->_websitePath . 'plugins/shopping/version.txt')) {
            $select = $dbAdapter->select()->from('shopping_config', array('value'))->where('name = ?', 'version');
            $dbVersion = $dbAdapter->fetchRow($select);
            $storeAlters = stristr(
                trim(Tools_Filesystem_Tools::getFile($this->_websitePath . '_install/store-alters.sql')),
                '-- version: ' . $dbVersion['value']
            );
        }

        $select = $dbAdapter->select()->from('config', array('value'))->where('name = ?', 'version');
        $dbVersion = $dbAdapter->fetchRow($select);
        $alters = stristr(
            trim(Tools_Filesystem_Tools::getFile($this->_websitePath . '_install/alters.sql')),
            '-- version: ' . $dbVersion['value']
        );
        if (!empty($storeAlters) && is_array($storeAlters)) {
            $alters = array_merge($alters, $storeAlters);
        }

        $sqlAlters = Tools_System_SqlSplitter::split($alters);
        try {
            foreach ($sqlAlters as $alter) {
                $dbAdapter->query($alter);
            }
            return true;
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }
}
