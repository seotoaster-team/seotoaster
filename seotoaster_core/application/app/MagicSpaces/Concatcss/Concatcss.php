<?php
class MagicSpaces_Concatcss_Concatcss extends Tools_MagicSpaces_Abstract {

    const FILE_NAME_PREFIX  = 'concat_';

    private $_disableForRoles = array(
        Tools_Security_Acl::ROLE_SUPERADMIN,
        Tools_Security_Acl::ROLE_ADMIN
    );

    private $_cssOrder      = array(
        'reset.css',
        'content.css',
        'nav.css',
        'style.css'
    );

    private $_themeFullPath = '';

    private $_fileCode      = '';

    private $_folderСssPath = '';

    private $_cacheable     = true;

    private $_cache         = null;

    private $_cacheId       = null;

    private $_cachePrefix   = 'magicspaces_';

    private $_cacheTags     = array('concatcss');

    private $_cacheWeek     = Helpers_Action_Cache::CACHE_WEEK;

    protected function _init() {
        parent::_init();

        if (!empty($this->_toasterData)) {
            $this->_themeFullPath = $this->_toasterData['themePath'].$this->_toasterData['currentTheme'].'/';
            $this->_fileCode      = substr(md5($this->_toasterData['templateId']), 0, 14);
            $folderСssPath        = $this->_themeFullPath.Tools_Theme_Tools::FOLDER_CSS;
            $this->_folderСssPath = (is_dir($folderСssPath)) ? $folderСssPath : $this->_themeFullPath;
        }
    }

    protected function _run() {
        $currentRole   = Zend_Controller_Action_HelperBroker::getStaticHelper('Session')->getCurrentUser()->getRoleId();
        $developerMode = Zend_Controller_Action_HelperBroker::getStaticHelper('config')->getConfig(
            'enableDeveloperMode'
        );

        // Disable of the compressor for the role admin/superadmin, version IE < 9, and when activated developerMode = 1
        if (empty($this->_toasterData)
            || (bool) $developerMode
            || !$this->_isBrowserIe()
            || in_array($currentRole, $this->_disableForRoles)
        ) {
            return $this->_spaceContent;
        }

        $filePath = null;
        if ($this->_cacheable === true) {
            $this->_cache   = Zend_Controller_Action_HelperBroker::getStaticHelper('Cache');
            $this->_cacheId = strtolower(get_called_class()).'_'.$this->_fileCode;

            if (!file_exists($this->_folderСssPath.self::FILE_NAME_PREFIX.$this->_fileCode.'.css')) {
                $this->_cache->clean($this->_cacheId, $this->_cachePrefix);
            }

            if (null === ($filePath = $this->_cache->load($this->_cacheId, $this->_cachePrefix))) {
                $this->_cacheTags[] = $this->_toasterData['templateId'];
                foreach ($this->_getFilesCss() as $file) {
                    $this->_cacheTags[] = preg_replace('/[^\w\d_]/', '', basename($file));
                }

                $content = $this->_getContent();
                if (trim($content) == '') {
                    return $this->_spaceContent;
                }
                $filePath = $this->_createFile($content);

                try {
                    $this->_cache->save(
                        $this->_cacheId,
                        $filePath,
                        $this->_cachePrefix,
                        $this->_cacheTags,
                        $this->_cacheWeek
                    );
                }
                catch (Exceptions_SeotoasterException $ste) {
                    return $ste->getMessage();
                }
            }
            elseif ($filePath === false) {
                $content = $this->_getContent();
                if (trim($content) == '') {
                    return $this->_spaceContent;
                }
                $filePath = $this->_createFile($content);
            }
        }
        else {
            $content = $this->_getContent();
            if (trim($content) == '') {
                return $this->_spaceContent;
            }
            $filePath = $this->_createFile($content);
        }

        $fileLink = $this->_toasterData['websiteUrl'].$filePath;

        return '<link href="'.$fileLink.'" rel="stylesheet" type="text/css" media="screen"/>';
    }

    private function _isBrowserIe($notBelowVersion = 9) {
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

    private function _getFilesCss() {
        $cssToTemplate = array();
        preg_match_all('/<link.*href="([^"]*\.css)".*>/', $this->_spaceContent, $cssToTemplate);

        $files = array();
        foreach ($cssToTemplate[1] as $file) {
            $link    = explode($this->_toasterData['websiteUrl'].$this->_themeFullPath, rawurldecode($file));
            $files[] = end($link);
        }

        return (isset($this->_params[0]) && $this->_params[0] == 'sort') ? $this->_sortCss($files) : $files;
    }

    private function _sortCss($files) {
        if (empty($files)) {
            return array();
        }

        $cssOrder = array();
        foreach ($this->_cssOrder as $key => $val) {
            $cssOrder[$key] =
                (in_array(Tools_Theme_Tools::FOLDER_CSS.$val, $files)) ? Tools_Theme_Tools::FOLDER_CSS.$val : $val;
        }

        $files = array_unique($files);
        $othersThemeCss  = array_diff($files, $cssOrder);
        $defaultThemeCss = array_intersect($cssOrder, $files);
        $files = array_merge($defaultThemeCss, $othersThemeCss);

        return $files;
    }

    private function _getContent() {
        $content    = '';
        $compressor = new CssMin();

        foreach ($this->_getFilesCss() as $file) {
            if (!file_exists($this->_themeFullPath.$file)) {
                continue;
            }

            $fileName = explode('/', $this->_themeFullPath.$file);
            $fileName = strtoupper(end($fileName));
            $content .= "/**** ".$fileName." start ****/\n";
            $content .= $compressor->run(
                preg_replace('~\@charset\s\"utf-8\"\;~Ui', '', file_get_contents($this->_themeFullPath.$file))
            );
            $content .= "\n/**** ".$fileName." end ****/\n";
        }

        return $content;
    }

    private function _createFile($content) {
        $filePath = $this->_folderСssPath.self::FILE_NAME_PREFIX.$this->_fileCode.'.css';

        try {
            Tools_Filesystem_Tools::saveFile($filePath, $content);
        }
        catch (Exceptions_SeotoasterException $ste) {
            return $ste->getMessage();
        }

        return str_replace(' ', '%20', $filePath);
    }
}
