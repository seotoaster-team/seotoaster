<?php
/**
 * Themes.php
 *
 * @author Eugene I. Nezhuta <theneiam@gmail.com>
 * Date: 12/4/12
 * Time: 5:23 PM
 */
class Api_Toaster_Themes extends Api_Service_Abstract {

    const THEME_SQL_FILE           = 'theme.sql';

    const THEME_MEDIA_DIR          = 'media';

    const THEME_PAGE_TEASERS_DIR   = 'previews';

    const THEME_KIND_LIGHT         = 'light';

    const THEME_KIND_FULL          = 'full';

    protected $_websiteHelper      = null;

    protected $_themesConfig       = array();

    protected $_configHelper       = null;

    protected $_cacheHelper        = null;

    protected $_protectedTemplates = array('index', 'default', 'category');

    protected $_translator         = null;

    protected $_fullThemesSqlMap   = array(
        'page'          => 'SELECT * FROM `page`;',
        'container'     => 'SELECT * FROM `container`;',
        'featured_area' => 'SELECT * FROM `featured_area`;',
        'page_fa'       => 'SELECT * FROM `page_fa`;'
    );

    protected $_accessList         = array(
        Tools_Security_Acl::ROLE_SUPERADMIN => array(
            'allow' => array('get', 'post', 'put', 'delete')
        ),
        Tools_Security_Acl::ROLE_USER => array(
            'allow' => array('get', 'put')
        )
    );

    public function init() {
        $this->_websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
        $this->_configHelper  = Zend_Controller_Action_HelperBroker::getStaticHelper('config');
        $this->_cacheHelper   = Zend_Controller_Action_HelperBroker::getStaticHelper('cache');
        $this->_themesConfig  = Zend_Registry::get('theme');
        $this->_translator    = Zend_Registry::get('Zend_Translate');
    }

    /**
     * Get a list of themes or start theme download
     *
     * @return array
     */
    public function getAction() {
        $themesPath = $this->_websiteHelper->getPath() . $this->_themesConfig['path'];

        // if parameter 'name' specified in the query, we assume user is trying to download a theme
        if($this->_request->has('name')) {
            $themeName    = filter_var($this->_request->getParam('name'), FILTER_SANITIZE_STRING);
            $themePath    = $themesPath . $themeName;

            if($this->_request->has('kind') && $this->_request->getParam('kind') == self::THEME_KIND_FULL) {
                $this->_saveFullThemeData($themeName);
            }

            $themeArchive = Tools_System_Tools::zip($themePath, $themeName);
            $this->_response->clearAllHeaders()->clearBody();
            $this->_response->setHeader('Content-Disposition', 'attachment; filename=' . $themeName . '.zip')
                ->setHeader('Content-Type', 'application/zip', true)
                ->setHeader('Content-Transfer-Encoding', 'binary', true)
                ->setHeader('Expires', date(DATE_RFC1123), true)
                ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)
                ->setHeader('Pragma', 'public', true)
                ->setHeader('Content-Length', filesize($themeArchive), true)
                ->setBody(file_get_contents($themeArchive))
                ->sendResponse();
            exit;
        }
        // themes list request
        $themesList = array();
        $themesDirs = Tools_Filesystem_Tools::scanDirectoryForDirs($themesPath);
        if(empty($themesDirs)) {
            $this->_error('Aw! No themes found!', self::REST_STATUS_NOT_FOUND);
        }
        foreach ($themesDirs as $themeName) {
            $files         = Tools_Filesystem_Tools::scanDirectory($themesPath . $themeName, false, false);
            $requiredFiles = preg_grep('/^(' . implode('|', $this->_protectedTemplates) . ')\.html$/i', $files);
            if(sizeof($requiredFiles) != sizeof($this->_protectedTemplates)) {
                continue;
            }
            $previews = preg_grep('/^preview\.(png|jpg|gif)$/i', $files);
            array_push($themesList, array(
                'name'      => $themeName,
                'preview'   => !empty ($previews) ? $this->_websiteHelper->getUrl() . $this->_themesConfig['path'] . $themeName . '/' . reset($previews) : $this->_websiteHelper->getUrl() . 'system/images/noimage.png',
                'isCurrent' => ($this->_configHelper->getConfig('currentTheme') == $themeName)
            ));
        }
        if(empty($themesList)) {
            $this->_error('Aw! No themes found!', self::REST_STATUS_NOT_FOUND);
        }
        return $themesList;
    }

    /*
     * Apply theme
     *
     */
    public function putAction() {
        //backup current theme
        $this->_saveFullThemeData();

        $themeName        = filter_var($this->_request->getParam('name'), FILTER_SANITIZE_STRING);
        $themePath        = $this->_websiteHelper->getPath() . $this->_themesConfig['path'] . $themeName;
        if(is_dir($themePath)) {
            //save templates in the database with proper type from theme.ini + proccess theme.sql + import media folder
            $this->_applyTemplates($themeName);
            if(file_exists($themePath . DIRECTORY_SEPARATOR . self::THEME_SQL_FILE)) {
                $this->_applySql($themeName);
            }

            //applying media content
            $themeMediaPath       = $themePath . DIRECTORY_SEPARATOR . self::THEME_MEDIA_DIR;
            $themePageTeasersPath = $themePath . DIRECTORY_SEPARATOR . self::THEME_PAGE_TEASERS_DIR;
            $this->_applyMedia(array(
                $themeMediaPath       => $this->_websiteHelper->getPath() . $this->_websiteHelper->getMedia(),
                $themePageTeasersPath => $this->_websiteHelper->getPath() . $this->_websiteHelper->getPreview()
            ));
            $this->_cacheHelper->clean(false, false);
        }
    }

    public function deleteAction() {
        $themeName = filter_var($this->_request->getParam('name'), FILTER_SANITIZE_STRING);
        if($this->_configHelper->getConfig('currentTheme') == $themeName) {
            $this->_error('Current theme cannot be removed!', self::REST_STATUS_FORBIDDEN);
        }
        return Tools_Filesystem_Tools::deleteDir($this->_websiteHelper->getPath() . $this->_themesConfig['path'] . $themeName);
    }


    private function _applyTemplates($themeName) {
        $themePath   = $this->_websiteHelper->getPath() . $this->_themesConfig['path'] . $themeName;
        $themeFiles  = Tools_Filesystem_Tools::findFilesByExtension($themePath, '(html|htm)', true, true, false);
        $themeConfig = false;
        $errors      = array();

        //check we are not missing any required template
        foreach($this->_protectedTemplates as $template){
            if (!array_key_exists($template, $themeFiles)){
                array_push($errors, $this->_translator->translate('Theme missing %s\$1 template', $template));
            }
        }

        if(!empty($errors)) {
            $this->_error(join('<br />', $errors), self::REST_STATUS_BAD_REQUEST);
        }

        //trying to get theme.ini file with templates presets
        try {
            $themeConfig = parse_ini_string(Tools_Filesystem_Tools::getFile($themePath . '/' . Tools_Template_Tools::THEME_CONFIGURATION_FILE));
        } catch (Exception $e) {
            $themeConfig = false;
        }

        $mapper = Application_Model_Mappers_TemplateMapper::getInstance();
        $mapper->clearTemplates(); // this will remove all templates except system required. @see $_protectedTemplates
        foreach($themeFiles as $templateName => $templateFile) {
            $template = $mapper->find($templateName);
            if (!$template instanceof Application_Model_Models_Template) {
                $template = new Application_Model_Models_Template();
                $template->setName($templateName);
            }
            //no matter add or edit -> we are setting the type if we can
            if(is_array($themeConfig) && !empty($themeConfig)) {
                if(array_key_exists($templateName, $themeConfig)) {
                    $template->setType($themeConfig[$templateName]);
                }
            }

            // getting template content
            try{
                $template->setContent(Tools_Filesystem_Tools::getFile($templateFile));
            } catch (Exceptions_SeotoasterException $e){
                array_push($errors, 'Can\'t read template file: ' . $templateName);
            }

            // saving template to db
            $mapper->save($template);
            unset($template);
        }

        //updating config table
        Application_Model_Mappers_ConfigMapper::getInstance()->save(array('currentTheme' => $themeName));
        if(!empty($errors)) {
            $this->_error(join('<br />', $errors, self::REST_STATUS_BAD_REQUEST));
        }
        return true;
    }

    private function _applySql($themeName) {
        try {
            $themeSql = Tools_Filesystem_Tools::getFile($this->_websiteHelper->getPath() . $this->_themesConfig['path'] . $themeName . '/' . self::THEME_SQL_FILE);
        } catch(Exception $e) {
            error_log($e->getMessage());
            return false;
        }
        if(!strlen($themeSql)) {
            return false;
        }
        $queries = Tools_System_SqlSplitter::split($themeSql);
        if(!is_array($queries) || empty($queries)) {
            return false;
        }
        $dbAdapter = Zend_Registry::get('dbAdapter');
        try {

            $dbAdapter->query('SET foreign_key_checks = 0;');

            // cleaning needed tables before apply a full theme
            array_walk(array_keys($this->_fullThemesSqlMap), function($table) use($dbAdapter) {
                $dbAdapter->query('DELETE FROM `' .$table. '`;');
            });

            //clean optimize table
            $dbAdapter->query('DELETE FROM `optimized`;');

            array_walk($queries, function($query) use ($dbAdapter) {
                if(strlen(trim($query))) {
                    $dbAdapter->query($query);
                }
            });

            $dbAdapter->query('SET foreign_key_checks = 1;');
        }
        catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    public function postAction() {}

    protected function _exportSql($themeName) {
        $themePath   = $this->_websiteHelper->getPath() . $this->_themesConfig['path'] . $themeName;
        $sql         = '';
        array_walk($this->_fullThemesSqlMap, function($query, $table) use(&$sql) {
            $sql .= Tools_Theme_Tools::dump($table, $query);
        });
        try {
            Tools_Filesystem_Tools::saveFile($themePath . DIRECTORY_SEPARATOR . self::THEME_SQL_FILE, $sql);
        } catch (Exceptions_SeotoasterException $se) {
            error_log($se->getMessage());
        }
    }

    protected function _applyMedia($pathesMap, $createSource = false) {
        if(!is_array($pathesMap) || empty($pathesMap)) {
            return;
        }
        $errors = array();
        foreach($pathesMap as $themeMediaDir => $toasterMediaDir) {
            if(!is_dir($themeMediaDir)) {
                if($createSource) {
                    Tools_Filesystem_Tools::mkDir($$themeMediaDir);
                }
                continue;
            }
            try {
                Tools_Filesystem_Tools::copy($themeMediaDir, $toasterMediaDir, array('.git', '.gitignore'));
            } catch (Exceptions_SeotoasterException $se) {
                $errors[] = $se->getMessage();
            }
        }
        return (!empty($errors)) ? $errors : true;
    }

    protected function _saveFullThemeData($themeName = '') {
        if(!$themeName) {
            $themeName = $this->_configHelper->getConfig('currentTheme');
        }
        $themePath = $this->_websiteHelper->getPath() . $this->_themesConfig['path'] . $themeName;

        $this->_applyMedia(array(
            $this->_websiteHelper->getPath() . $this->_websiteHelper->getPreview() => $themePath . DIRECTORY_SEPARATOR . self::THEME_PAGE_TEASERS_DIR,
            $this->_websiteHelper->getPath() . $this->_websiteHelper->getMedia()   => $themePath . DIRECTORY_SEPARATOR . self::THEME_MEDIA_DIR
        ), true);

        //exporting sql for the full theme
        $this->_exportSql($themeName);
    }
}
