<?php

/**
 * Concatenate css into one file and minify css code
 */
class MagicSpaces_Concatcss_Concatcss extends Tools_MagicSpaces_Abstract
{
    const FILE_NAME_PREFIX       = 'concat_';

    protected  $_disableForRoles = array(
        Tools_Security_Acl::ROLE_SUPERADMIN,
        Tools_Security_Acl::ROLE_ADMIN
    );

    protected $_cssOrder         = array(
        'reset.css',
        'content.css',
        'nav.css',
        'style.css'
    );

    protected $_themeFullPath    = '';

    protected $_fileId           = '';

    protected $_folderСssPath    = '';

    protected $_cacheable        = true;

    protected $_cache            = null;

    protected $_cacheId          = null;

    protected $_cachePrefix      = 'magicspaces_';

    protected $_cacheTags        = array('concatcss');

    protected $_cacheLifeTime    = Helpers_Action_Cache::CACHE_WEEK;

    protected function _init()
    {
        parent::_init();

        $this->_themeFullPath = $this->_toasterData['themePath'].$this->_toasterData['currentTheme'];
        $this->_fileId = substr(md5($this->_toasterData['templateId']), 0, 14);
        $folderСssPath = $this->_themeFullPath.DIRECTORY_SEPARATOR.Tools_Theme_Tools::FOLDER_CSS.DIRECTORY_SEPARATOR;
        $this->_folderСssPath = (is_dir($folderСssPath)) ? $folderСssPath : $this->_themeFullPath.DIRECTORY_SEPARATOR;
    }

    protected function _run()
    {
        $currentRole   = Zend_Controller_Action_HelperBroker::getStaticHelper('Session')->getCurrentUser()->getRoleId();
        $developerMode = Zend_Controller_Action_HelperBroker::getStaticHelper('config')->getConfig(
            'enableDeveloperMode'
        );

        // Disable of the compressor for the role admin/superadmin, version IE < 9, and when activated developerMode = 1
        if (empty($this->_toasterData)
            || (bool) $developerMode
            || !Tools_System_Tools::isBrowserIe()
            || in_array($currentRole, $this->_disableForRoles)
        ) {
            return $this->_spaceContent;
        }

        if ($this->_cacheable === true) {
            $this->_cache   = Zend_Controller_Action_HelperBroker::getStaticHelper('Cache');
            $this->_cacheId = strtolower(get_called_class()).'_'.$this->_fileId;

            if (!file_exists($this->_folderСssPath.self::FILE_NAME_PREFIX.$this->_fileId.'.css')) {
                $this->_cache->clean($this->_cacheId, $this->_cachePrefix);
            }

            if (null === ($filePath = $this->_cache->load($this->_cacheId, $this->_cachePrefix))) {
                $this->_cacheTags[] = preg_replace('/[^\w\d_]/', '', $this->_toasterData['templateId']);
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
                        $this->_cacheLifeTime
                    );
                }
                catch (Exceptions_SeotoasterException $ste) {
                    return $ste->getMessage();
                }
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

    /**
     * Returns a list of files in the current template
     *
     * @return array
     */
    private function _getFilesCss()
    {
        $files = array();
        preg_match_all(
            '/<link.*href=".*\/(?:('.Tools_Theme_Tools::FOLDER_CSS.'\/.*\.css)|.*\/(.*\.css))".*>/',
            $this->_spaceContent,
            $files
        );
        $files = array_filter(array_merge($files[1], $files[2]));

        return (isset($this->_params[0]) && $this->_params[0] == 'sort') ? $this->_sortCss($files) : $files;
    }

    /**
     * Sorts files in correspondence to $ _cssOrder
     *
     * @param $files
     * @return array
     */
    private function _sortCss($files)
    {
        if (empty($files)) {
            return array();
        }

        $cssOrder = array();
        foreach ($this->_cssOrder as $key => $val) {
            $cssOrder[$key] = (in_array(Tools_Theme_Tools::FOLDER_CSS.DIRECTORY_SEPARATOR.$val, $files))
                ? Tools_Theme_Tools::FOLDER_CSS.DIRECTORY_SEPARATOR.$val
                : $val;
        }

        $files = array_unique($files);
        $othersThemeCss  = array_diff($files, $cssOrder);
        $defaultThemeCss = array_intersect($cssOrder, $files);
        $files = array_merge($defaultThemeCss, $othersThemeCss);

        return $files;
    }

    /**
     * Returns unified content
     *
     * @return string
     */
    private function _getContent()
    {
        $content    = '';
        $compressor = new CssMin();

        foreach ($this->_getFilesCss() as $file) {
            if (!file_exists($this->_themeFullPath.DIRECTORY_SEPARATOR.$file)) {
                continue;
            }

            $fileName = strtoupper(basename($this->_themeFullPath.DIRECTORY_SEPARATOR.$file));
            $content .= '/**** '.$fileName.' start ****/'.PHP_EOL;
            $content .= $compressor->run(
                preg_replace(
                    '~\@charset\s\"utf-8\"\;~Ui',
                    '',
                    file_get_contents($this->_themeFullPath.DIRECTORY_SEPARATOR.$file)
                )
            );
            $content .= PHP_EOL.'/**** '.$fileName.' end ****/'.PHP_EOL;
        }

        return $content;
    }

    /**
     * Save the file, returns file path
     *
     * @param $content
     * @return mixed|string
     */
    private function _createFile($content)
    {
        $filePath = $this->_folderСssPath.self::FILE_NAME_PREFIX.$this->_fileId.'.css';

        try {
            Tools_Filesystem_Tools::saveFile($filePath, $content);
        }
        catch (Exceptions_SeotoasterException $ste) {
            return $ste->getMessage();
        }

        return str_replace(' ', '%20', $filePath);
    }
}
