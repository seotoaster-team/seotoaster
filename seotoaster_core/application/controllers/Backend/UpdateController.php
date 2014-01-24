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

    protected $_downloadLink = null;
    protected $_toasterVersion = null;
    protected $_remoteVersion = null;
    protected $_websitePath = null;
    protected $_tmpPath = null;

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
        $this->_tmpPath = $this->_helper->website->getPath() . $this->_helper->website->getTmp();
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
            }
        }
        $updateStatus = version_compare($this->_remoteVersion, $this->_toasterVersion);
        if (1 === $updateStatus) {
            $this->_getZip();
            $this->_copyConfigs();
            $this->_copyToaster($this->_tmpPath . 'updates', $this->_websitePath);
        } elseif (-1 === $updateStatus) {
            return 'Your version of the system is higher than the remote';
        } else {
            return 'Your system up to date';
        }
    }

    protected function _getZip()
    {
        $upZip = $this->_tmpPath . 'toaster.zip';
        $upDir = $this->_tmpPath . 'updates';
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

    protected function _copyConfigs()
    {

        if (!copy($this->_websitePath . 'system/coreinfo.php', $this->_tmpPath . 'updates/system/coreinfo.php')) {
            echo "can't copy";
        }
        $confDir = $this->_websitePath . 'seotoaster_core/application/configs/';
        $upConfDir = $this->_tmpPath . 'updates/seotoaster_core/application/configs/';
        $dir = opendir($confDir);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                copy($confDir . '/' . $file, $upConfDir . '/' . $file);
            }
        }
        closedir($dir);
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
            }
        }

    }
}
