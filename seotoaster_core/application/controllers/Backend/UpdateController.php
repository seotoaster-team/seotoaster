<?php
/**
 * UpdateController - handler for upadate
 *
 * @author Vitaly Vyrodov <vitaly.vyrodov@gmail.com>
 */

class Backend_UpdateController extends Zend_Controller_Action
{

    const MASTER_CMS_LINK       = 'http://seotoaster.com/cms.txt';
    const MASTER_STORE_LINK     = 'http://seotoaster.com/store.txt';

    protected $_downloadLink    = null;
    protected $_toasterVersion  = null;
    protected $_remoteVersion   = null;
    protected $_websitePath     = null;
    protected $_tmpPath         = null;

    public function init()
    {
        parent::init();
        if (!Tools_Security_Acl::isAllowed(Tools_Security_Acl::ROLE_ADMIN)) {
            $this->_redirect($this->_helper->website->getUrl(), array('exit' => true));
        }
    }

    public function updateAction()
    {
        $this->_websitePath = $this->_helper->website->getPath();
        $this->_tmpPath = $this->_helper->website->getPath() . $this->_helper->website->getTmp() . 'updates';
        try {
            if (file_exists($this->_websitePath . 'plugins/shopping/version.txt')) {
                $this->_toasterVersion = trim(Tools_Filesystem_Tools::getFile($this->_websitePath . 'plugins/shopping/version.txt'));
                $master_link = self::MASTER_STORE_LINK;
            } else {
                $this->_toasterVersion = trim(Tools_Filesystem_Tools::getFile('version.txt'));
                $master_link = self::MASTER_CMS_LINK;
            }
            $master_versions = explode("\n", file_get_contents($master_link));
            $this->_remoteVersion   = filter_var($master_versions [0], FILTER_SANITIZE_STRING);
            $this->_downloadLink    = filter_var($master_versions [1], FILTER_SANITIZE_URL);
        } catch (Exceptions_SeotoasterException $se) {
            if (self::debugMode()) {
                error_log($se->getMessage());
            }
        }
        $updateStatus = version_compare($this->_remoteVersion, $this->_toasterVersion);
        if (1 === $updateStatus) {
            //$this->_getZip();
            //$this->_copyFiles();
            //$this->_copyToaster($this->_tmpPath, $this->_websitePath);
            //db TODO: delete hardcode
            $this->_updateDataBase('cms');
            $this->_redirect($this->_helper->website->getUrl(), array('exit' => true));
        } elseif (-1 === $updateStatus) {
            return 'Your version of the system is higher than the remote';
        } else {
            return 'Your system up to date';
        }
    }

    protected function _getZip()
    {
        $upZip = $this->_helper->website->getTmp() . 'toaster.zip';
        $upDir = $this->_tmpPath;
        mkdir($upDir);
        set_time_limit(0);
        $fp = fopen($upZip, 'w+');
        $ch = curl_init($this->_downloadLink);
        curl_setopt($ch, CURLOPT_TIMEOUT, 100);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
        $filter = new Zend_Filter_Decompress(
            array(
                'adapter' => 'Zip',
                'options' => array(
                    'target' => $upDir,
                )
            )
        );
        $filter->filter($upZip);
    }

    protected function _copyFiles()
    {
        if (!copy($this->_websitePath . 'system/coreinfo.php', $this->_tmpPath . DIRECTORY_SEPARATOR . 'system/coreinfo.php')) {
            return false;
        }
        mkdir( $this->_tmpPath . DIRECTORY_SEPARATOR . '_install');
        $this->_copyToaster($this->_tmpPath . DIRECTORY_SEPARATOR . 'install', $this->_tmpPath . DIRECTORY_SEPARATOR . '_install');
        return true;
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

    protected function _updateDataBase($type = 'cms')
    {
        //TODO: Add more checks.
        $alters = stristr(trim(Tools_Filesystem_Tools::getFile($this->_websitePath . '_install/alters.sql')), '-- version: ' .  $this->_toasterVersion);
        $sqlAlters = Tools_System_SqlSplitter::split($alters);
        $db = new Zend_Db_Table();
        //$db->getAdapter()->beginTransaction();
        foreach ($sqlAlters as $alter) {
            $db->getAdapter()->insert(array($alter));
        }
        //$db->getAdapter()->endTransaction();
    }
}
