<?php
class MagicSpaces_Concatcss_Concatcss extends Tools_MagicSpaces_Abstract {

    const FILE_NAME_PREFIX  = 'concat_';

    const FOLDER_CSS        = 'css/';

    private $_cssOrder      = array(
        'reset.css',
        'content.css',
        'nav.css',
        'style.css'
    );

    private $_themeFullPath = '';

    protected $_cache       = null;

    protected $_cacheId     = null;

    protected $_cachePrefix = 'magicspaces_';

    protected $_cacheable   = true;

    protected $_cacheTags   = array();

    protected function _init() {
        parent::_init();

        if (!empty($this->_toasterData)) {
            $this->_themeFullPath = $this->_toasterData['themePath'].$this->_toasterData['currentTheme'].'/';
        }
    }

    protected function _run() {
        if (Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_CONTENT)) { // Adminpanel
            return $this->_spaceContent;   
        }
        
        $content = null;
        if ($this->_cacheable === true) {
            $cacheHelper = Zend_Controller_Action_HelperBroker::getExistingHelper('cache');
            $cacheKey    = $this->_cacheKey();

            if (null === ($content = $cacheHelper->load($cacheKey, $this->_cachePrefix))) {
                $cssTag = array();
                foreach ($this->_templateFiles() as $file) {
                    $cssTag[] = preg_replace('/[^\w\d_]/', '', basename($file));
                }
                $this->_cacheTags   = array_merge($this->_cacheTags, $cssTag);
                $this->_cacheTags[] = $this->_toasterData['templateId'];

                $content = $this->_generatorFiles();
                try {
                    $this->_cache->save($cacheKey, $content, $this->_cachePrefix, $this->_cacheTags);
                }
                catch (Exceptions_SeotoasterException $ste) {
                    $content = $ste->getMessage();
                }
            }
            elseif ($content === false) {
                $content = $this->_generatorFiles();
            }
        }
        else {
            $content = $this->_generatorFiles();
        }

        return $content;
    }

    private function _cacheKey() {
        $this->_cache   = Zend_Controller_Action_HelperBroker::getStaticHelper('Cache');
        $this->_cacheId = strtolower(get_called_class());

        $roleId = Zend_Controller_Action_HelperBroker::getStaticHelper('Session')->getCurrentUser()->getRoleId();
        $this->_cacheId .= '_'.$roleId.'_'.substr(md5($this->_toasterData['templateId']), 0, 10);

        return $this->_cacheId;
    }

    private function _templateFiles() {
        $cssToTemplate = array();
        preg_match_all('/<link.*href="([^"]*\.css)".*>/', $this->_spaceContent, $cssToTemplate);

        $files = array();
        foreach ($cssToTemplate[1] as $file) {
            $files[] = end(explode($this->_toasterData['websiteUrl'].$this->_themeFullPath, rawurldecode($file)));
        }

        return $files;
    }

    private function _sortCss($files) {
        if (empty($files)) {
            return array();
        }

        $cssOrder = array();
        foreach ($this->_cssOrder as $key => $val) {
            $cssOrder[$key] = (in_array(self::FOLDER_CSS.$val, $files)) ? self::FOLDER_CSS.$val : $val;
        }

        $files = array_unique($files);
        $othersThemeCss  = array_diff($files, $cssOrder);
        $defaultThemeCss = array_intersect($cssOrder, $files);
        $files = array_merge($defaultThemeCss, $othersThemeCss);

        return $files;
    }

    private function _addCss($cssPath) {
        $cssContent = '';

        if (file_exists($cssPath)) {
            $fileName   = explode('/', $cssPath);
            $fileName   = strtoupper(end($fileName));
            $compressor = new CssMin();

            $cssContent .= "/**** ".strtoupper($fileName)." start ****/\n";
            $cssContent .= $compressor->run(preg_replace('~\@charset\s\"utf-8\"\;~Ui', '', file_get_contents($cssPath)));
            $cssContent .= "\n/**** ".strtoupper($fileName)." end ****/\n";
        }

        return $cssContent;
    }

    private function _generatorFiles() {
        $files = (isset($this->_params[0]) && $this->_params[0] == 'sort') ? $this->_sortCss($this->_templateFiles()) : $this->_templateFiles();
        $concatContent = '';
        foreach ($files as $file) {
            $concatContent .= $this->_addCss($this->_themeFullPath.$file);
        }

        $filePath = (is_dir($this->_themeFullPath.self::FOLDER_CSS)) ? $this->_themeFullPath.self::FOLDER_CSS : $this->_themeFullPath;
        $fileName = self::FILE_NAME_PREFIX.substr(md5($this->_toasterData['templateId']), 0, 10).'.css';

        try {
            Tools_Filesystem_Tools::saveFile($filePath.$fileName, $concatContent);
        }
        catch (Exceptions_SeotoasterException $ste) {
            return $ste->getMessage();
        }

        return '<link href="'.$this->_toasterData['websiteUrl'].str_replace(' ', '%20', $filePath).$fileName.'" rel="stylesheet" type="text/css" media="screen" />';
    }
}