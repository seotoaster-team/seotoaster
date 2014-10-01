<?php

/**
 * Controller for all stuff that belongs to theme, template, css.
 *
 */
class Backend_ThemeController extends Zend_Controller_Action
{

    const DEFAULT_CSS_NAME = 'style.css';
    const DEFAULT_JS_NAME = 'scripts.js';

    private $_websiteConfig = null;

    private $_themeConfig = null;

    private $_templatesOrder
        = array(
            'typeregular' => ''
        );

    public function init()
    {
        parent::init();
        if (!Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_THEMES)) {
            $this->redirect($this->_helper->website->getUrl(), array('exit' => true));
        }
        $this->view->websiteUrl = $this->_helper->website->getUrl();
        $this->_websiteConfig = Zend_Registry::get('website');
        $this->_themeConfig = Zend_Registry::get('theme');
        $this->_translator = Zend_Registry::get('Zend_Translate');
        $this->_helper->AjaxContext()->addActionContexts(
            array(
                'pagesviatemplate' => 'json',
            )
        )->initContext('json');

    }

    /**
     * Method returns template editing screen
     * and saves edited template
     */
    public function templateAction()
    {
        $templateForm = new Application_Form_Template();
        $templateName = $this->getRequest()->getParam('id');
        $mapper = Application_Model_Mappers_TemplateMapper::getInstance();
        $currentTheme = $this->_helper->config->getConfig('currentTheme');
        $currentThemePath = realpath($this->_websiteConfig['path'] . $this->_themeConfig['path'] . $currentTheme);
        if (!$this->getRequest()->isPost()) {
            $templateForm->getElement('pageId')->setValue($this->getRequest()->getParam('pid'));
            if ($templateName) {
                $template = $mapper->find($templateName);
                if ($template instanceof Application_Model_Models_Template) {
                    $templateContent = $template->getContent();
                    // If developerMode = 1, get template content from the template file
                    if ((bool)$this->_helper->config->getConfig('enableDeveloperMode')) {
                        $currentTemplatePath = $currentThemePath . DIRECTORY_SEPARATOR . $templateName . '.html';
                        if (file_exists($currentTemplatePath)) {
                            $templateContent = Tools_Filesystem_Tools::getFile($currentTemplatePath);
                        }
                    }
                    $templateForm->getElement('content')->setValue($templateContent);
                    $templateForm->getElement('name')->setValue($template->getName());
                    $templateForm->getElement('id')->setValue($template->getName());
                    $templateForm->getElement('templateType')->setValue($template->getType());
                    $templateForm->getElement('shortcuts');
                    $this->view->pagesUsingTemplate = Tools_Page_Tools::getPagesCountByTemplate($templateName);
                }
            }
        } else {
            if ($templateForm->isValid($this->getRequest()->getPost())) {
                $templateData = $templateForm->getValues();
                $originalName = $templateData['id'];

                if ($templateData['templateType'] === Application_Model_Models_Template::TYPE_MOBILE
                    || preg_match(
                        '~^mobile_~',
                        $templateData['name']
                    )
                ) {
                    $isMobileTemplate = true;
                    $templateData['name'] = 'mobile_' . preg_replace('~^mobile_~', '', $templateData['name']);
                    $templateData['templateType'] = Application_Model_Models_Template::TYPE_MOBILE;
                } else {
                    $isMobileTemplate = false;
                }

                //check if we received 'id' in request and try to find existing template with this id
                /**
                 * @var $template Application_Model_Models_Template
                 */
                if (!empty($originalName) && null !== ($template = $mapper->find($originalName))) {
                    $status = 'update';
                    // avoid renaming of system protected templates
                    if (!in_array($template->getName(), Tools_Theme_Tools::$protectedTemplates)) {
                        $template->setOldName($originalName);
                        $template->setName($templateData['name']);
                    } else {
                        // TODO throw error if trying to rename protected template
                    }
                    $template->setContent($templateData['content']);

                } else {
                    $status = 'new';
                    if ((bool)$this->_helper->config->getConfig('enableDeveloperMode')) {
                        $currentTemplatePath
                            = $currentThemePath . DIRECTORY_SEPARATOR . $templateData['name'] . '.html';
                        if (file_exists($currentTemplatePath)) {
                            $status = 'addedDb';
                        }
                    }

                    //if ID missing and name is not exists and name is not system protected - creating new template
                    if (in_array($templateData['name'], Tools_Theme_Tools::$protectedTemplates)
                        || null !== $mapper->find($templateData['name'])
                    ) {
                        $this->_helper->response->response(
                            $this->_translator->translate('Template with such name already exists'),
                            true
                        );
                    }
                    $template = new Application_Model_Models_Template($templateData);
                }
                $template->setType($templateData['templateType']);

                // saving/updating template in db
                if ($result = $mapper->save($template)) {
                    Tools_Theme_Tools::updateThemeIni(
                        $currentThemePath,
                        $templateData['name'],
                        $templateData['templateType']
                    );
                    $this->_helper->cache->clean(
                        false,
                        false,
                        array(preg_replace('/[^\w\d_]/', '', $template->getName()))
                    );
                }
                // saving to file in theme folder;
                $filepath = $currentThemePath . DIRECTORY_SEPARATOR;

                if ($isMobileTemplate) {
                    if (!is_dir($filepath . 'mobile')) {
                        Tools_Filesystem_Tools::mkDir($filepath . 'mobile');
                    }
                    $filepath .= preg_replace('~^mobile_~', 'mobile' . DIRECTORY_SEPARATOR, $template->getName());
                } else {
                    $filepath .= $templateData['name'];
                }
                $filepath .= '.html';

                try {
                    if ($filepath) {
                        Tools_Filesystem_Tools::saveFile($filepath, $templateData['content']);
                    }
                    if ($status === 'update' && ($template->getOldName() !== $template->getName())) {
                        $oldFilename = $currentThemePath . DIRECTORY_SEPARATOR;
                        if ($isMobileTemplate) {
                            $oldFilename .= preg_replace(
                                '~^mobile_~',
                                'mobile' . DIRECTORY_SEPARATOR,
                                $template->getOldName()
                            );
                        } else {
                            $oldFilename .= $template->getOldName();
                        }
                        $oldFilename .= '.html';
                        if (is_file($oldFilename)) {
                            if (false === Tools_Filesystem_Tools::deleteFile($oldFilename)) {

                            }
                        }
                        unset($oldFilename);
                    }
                } catch (Exceptions_SeotoasterException $e) {
                    Tools_System_Tools::debugMode() && error_log($e->getMessage());
                }
                $this->_helper->cache->clean(
                    Helpers_Action_Cache::KEY_PLUGINTABS,
                    Helpers_Action_Cache::PREFIX_PLUGINTABS
                );

                $this->_helper->response->response($status, false);

            } else {
                $errorMessages = array();
                $validationErrors = $templateForm->getErrors();
                $messages = array(
                    'name'    => array(
                        'isEmpty'              => 'Template name field can\'t be empty.',
                        'notAlnum'             => 'Template name contains characters which are non alphabetic and no digits',
                        'stringLengthTooLong'  => 'Template name field is too long.',
                        'stringLengthTooShort' => 'Template name field is too short.'
                    ),
                    'content' => array(
                        'isEmpty' => 'Content can\'t be empty.'
                    )
                );
                foreach ($validationErrors as $element => $errors) {
                    if (empty ($errors)) {
                        continue;
                    }
                    foreach ($messages[$element] as $n => $message) {
                        if (in_array($n, $errors)) {
                            array_push($errorMessages, $message);
                        }
                    }
                }
                $this->_helper->response->response($errorMessages, true);
            }
        }
        $this->view->helpSection = 'addtemplate';
        $this->view->templateForm = $templateForm;
    }

    /**
     * Method return form for editing css files for current theme
     * and saves css file content
     */
    public function editcssAction()
    {
        $cssFiles = $this->_buildCssFileList();
        $defaultCss = $this->_websiteConfig['path'] . $this->_themeConfig['path'] . array_search(
                self::DEFAULT_CSS_NAME,
                current($cssFiles)
            );

        $editcssForm = new Application_Form_Css();
        $editcssForm->getElement('cssname')->setMultiOptions($cssFiles);
        $editcssForm->getElement('cssname')->setValue(self::DEFAULT_CSS_NAME);

        //checking, if form was submited via POST then
        if ($this->getRequest()->isPost()) {
            $postParams = $this->getRequest()->getParams();
            if (isset($postParams['getcss']) && !empty ($postParams['getcss'])) {
                $cssName = $postParams['getcss'];
                try {
                    $content = Tools_Filesystem_Tools::getFile(
                        $this->_websiteConfig['path'] . $this->_themeConfig['path'] . $cssName
                    );
                    $this->_helper->response->response($content, false);
                } catch (Exceptions_SeotoasterException $e) {
                    $this->_helper->response->response($e->getMessage(), true);
                }
            } else {
                if (is_string($postParams['content']) && empty($postParams['content'])) {
                    $editcssForm->getElement('content')->setRequired(false);
                }
                if ($editcssForm->isValid($postParams)) {
                    $cssName = $postParams['cssname'];
                    try {
                        Tools_Filesystem_Tools::saveFile(
                            $this->_websiteConfig['path'] . $this->_themeConfig['path'] . $cssName,
                            $postParams['content']
                        );
                        $params = array(
                            'websiteUrl'   => $this->_helper->website->getUrl(),
                            'themePath'    => $this->_websiteConfig['path'] . $this->_themeConfig['path'],
                            'currentTheme' => $this->_helper->config->getConfig('currentTheme')
                        );
                        $concatCss = Tools_Factory_WidgetFactory::createWidget(
                            'Concatcss',
                            array('refresh' => true),
                            $params
                        );
                        $concatCss->render();
                        $this->_helper->response->response($this->_translator->translate('CSS saved'), false);
                    } catch (Exceptions_SeotoasterException $e) {
                        $this->_helper->response->response($e->getMessage(), true);
                    }
                }
            }
            $this->_helper->response->response($this->_translator->translate('Undefined error'), true);
        } else {
            try {
                $editcssForm->getElement('content')->setValue(Tools_Filesystem_Tools::getFile($defaultCss));
                $editcssForm->getElement('cssname')->setValue(array_search(self::DEFAULT_CSS_NAME, current($cssFiles)));
            } catch (Exceptions_SeotoasterException $e) {
                $this->view->errorMessage = $e->getMessage();
            }
        }
        $this->view->helpSection = 'editcss';
        $this->view->editcssForm = $editcssForm;
    }

    /**
     * Method return form for editing js files for current theme
     * and saves js file content
     */
    public function editjsAction()
    {
        $jsFiles = $this->_buildJSFileList();
        $defaultJS = $this->_websiteConfig['path'] . $this->_themeConfig['path'] . array_search(
                self::DEFAULT_JS_NAME,
                current($jsFiles)
            );

        $editjsForm = new Application_Form_JS();
        $editjsForm->getElement('jsname')->setMultiOptions($jsFiles);
        $editjsForm->getElement('jsname')->setValue(self::DEFAULT_JS_NAME);

        //checking, if form was submited via POST then
        if ($this->getRequest()->isPost()) {
            $postParams = $this->getRequest()->getParams();
            if (isset($postParams['getjs']) && !empty ($postParams['getjs'])) {
                $jsName = $postParams['getjs'];
                try {
                    $content = Tools_Filesystem_Tools::getFile(
                        $this->_websiteConfig['path'] . $this->_themeConfig['path'] . $jsName
                    );
                    $this->_helper->response->response($content, false);
                } catch (Exceptions_SeotoasterException $e) {
                    $this->_helper->response->response($e->getMessage(), true);
                }
            } else {
                if (is_string($postParams['content']) && empty($postParams['content'])) {
                    $editjsForm->getElement('content')->setRequired(false);
                }
                if ($editjsForm->isValid($postParams)) {
                    $jsName = $postParams['jsname'];
                    try {
                        Tools_Filesystem_Tools::saveFile(
                            $this->_websiteConfig['path'] . $this->_themeConfig['path'] . $jsName,
                            $postParams['content']
                        );
                        $this->_minimizationJS($jsName, $postParams);
                        $this->_helper->response->response($this->_translator->translate('JS saved'), false);
                    } catch (Exceptions_SeotoasterException $e) {
                        $this->_helper->response->response($e->getMessage(), true);
                    }
                }
            }
            $this->_helper->response->response($this->_translator->translate('Undefined error'), true);
        } else {
            try {
                $editjsForm->getElement('content')->setValue(Tools_Filesystem_Tools::getFile($defaultJS));
                $editjsForm->getElement('jsname')->setValue(array_search(self::DEFAULT_JS_NAME, current($jsFiles)));
            } catch (Exceptions_SeotoasterException $e) {
                $this->view->errorMessage = $e->getMessage();
            }
        }
        $this->view->helpSection = 'editjs';
        $this->view->editjsForm = $editjsForm;
    }

    private function _minimizationJS($jsName, $postParams)
    {
        if ($postParams['jsminify'] === 'true' && $postParams['jscombine'] === 'false') {
            $jsPath = $this->_websiteConfig['path'] . $this->_themeConfig['path'] . $jsName;
            $jsContent = $postParams['content'];
            $jsContent = JSMin::minify($jsContent);
            Tools_Filesystem_Tools::saveFile(str_replace('.js', '.min.js', $jsPath), $jsContent);
        }
        if ($postParams['jscombine'] === 'true') {
            $jsContentCombine = null;
            $jsParentDirName = basename(dirname($jsName));
            $jsPath = $this->_websiteConfig['path'] . $this->_themeConfig['path'] . dirname($jsName);
            $jsList = glob($jsPath . DIRECTORY_SEPARATOR . '*.js');
            if (is_array($jsList)) {
                foreach ($jsList as $jsFile) {
                    $jsContent = file_get_contents($jsFile, true);
                    $jsContentCombine .= JSMin::minify($jsContent);
                }
            }
            Tools_Filesystem_Tools::saveFile(
                dirname($jsPath) . DIRECTORY_SEPARATOR . $jsParentDirName . '.min.js',
                $jsContentCombine
            );
        }
    }

    /**
     * Method build a list of css files for current theme
     * with subdirectories
     *
     * @return <type>
     */
    private function _buildCssFileList()
    {
        $currentThemeName = $this->_helper->config->getConfig('currentTheme');
        $currentThemePath = Tools_System_Tools::normalizePath(
            realpath($this->_websiteConfig['path'] . $this->_themeConfig['path'] . $currentThemeName)
        );

        $cssFiles = Tools_Filesystem_Tools::findFilesByExtension($currentThemePath, 'css', true);

        $cssTree = array();
        foreach ($cssFiles as $file) {
            // don't show concat css for editing
            if (preg_match(
                '/' . MagicSpaces_Concatcss_Concatcss::FILE_NAME_PREFIX . '[a-zA-Z0-9]+\.css/i',
                strtolower(basename($file))
            )
            ) {
                continue;
            }

            preg_match_all(
                '~^' . $currentThemePath . '/([a-zA-Z0-9-_\s/.]+/)*([a-zA-Z0-9-_\s.]+\.css)$~i',
                Tools_System_Tools::normalizePath($file),
                $sequences
            );
            $subfolders = $currentThemeName . '/' . $sequences[1][0];
            $files = array();
            foreach ($sequences[2] as $key => $value) {
                $files[$subfolders . $value] = $value;
            }

            if (!array_key_exists($subfolders, $cssTree)) {
                $cssTree[$subfolders] = array();
            }
            $cssTree[$subfolders] = array_merge($cssTree[$subfolders], $files);

        }

        return $cssTree;
    }

    /**
     * Method build a list of js files for current theme
     * with subdirectories
     *
     * @return <type>
     */
    private function _buildJSFileList()
    {
        $currentThemeName = $this->_helper->config->getConfig('currentTheme');
        $currentThemePath = Tools_System_Tools::normalizePath(
            realpath($this->_websiteConfig['path'] . $this->_themeConfig['path'] . $currentThemeName)
        );

        $jsFiles = Tools_Filesystem_Tools::findFilesByExtension($currentThemePath, 'js', true);
        $jsFiles = array_filter(preg_replace('~.*(min).js.*~', '', $jsFiles));
        $jsTree = array();

        foreach ($jsFiles as $file) {
            preg_match_all(
                '~^' . $currentThemePath . '/([a-zA-Z0-9-_\s/.]+/)*([a-zA-Z0-9-_\s.]+\.js)$~i',
                Tools_System_Tools::normalizePath($file),
                $sequences
            );
            $subfolders = $currentThemeName . '/' . $sequences[1][0];
            $files = array();
            foreach ($sequences[2] as $key => $value) {
                $files[$subfolders . $value] = $value;
            }

            if (!array_key_exists($subfolders, $jsTree)) {
                $jsTree[$subfolders] = array();
            }
            $jsTree[$subfolders] = array_merge($jsTree[$subfolders], $files);

        }

        return $jsTree;
    }

    private function _sortTemplates($templates = array())
    {
        if (empty($templates)) {
            return array();
        }
        $sortTemplates = array_intersect_key($templates, $this->_templatesOrder);
        $allTemplates = array_diff_key($templates, $this->_templatesOrder);

        return array_merge($sortTemplates, $allTemplates);
    }

    /**
     * Method returns list of templates or template content if id given in params (AJAX)
     *
     * @return html || json
     */
    public function gettemplateAction()
    {
        if ($this->getRequest()->isPost()) {
            $mapper = Application_Model_Mappers_TemplateMapper::getInstance();
            $listtemplates = $this->getRequest()->getParam('listtemplates');
            $additional = $this->getRequest()->getParam('additional');
            $pageId = $this->getRequest()->getParam('pageId');
            if ($pageId) {
                $page = Application_Model_Mappers_PageMapper::getInstance()->find($pageId);
            }
            $currentTheme = $this->_helper->config->getConfig('currentTheme');
            $types = $mapper->fetchAllTypes();
            if (array_key_exists($listtemplates, array_merge($types, array('all' => 'all')))) {
                $template = (isset($page) && $page instanceof Application_Model_Models_Page) ? $mapper->find(
                    $page->getTemplateId()
                ) : $mapper->find($listtemplates);
                $this->view->templates = $this->_getTemplateListByType(
                    $listtemplates,
                    $currentTheme,
                    ($template instanceof Application_Model_Models_Template) ? $template->getName() : ''
                );
                if (empty($this->view->templates) || !$this->view->templates) {
                    $this->_helper->response->response($this->_translator->translate('Template not found'), true);
                    return true;
                }
                $this->view->protectedTemplates = Tools_Theme_Tools::$protectedTemplates;
                $this->view->types = $this->_sortTemplates($types);
                echo $this->view->render($this->getViewScript('templateslist'));
            } else {
                // Enable editing directly from the template file
                if ((bool)$this->_helper->config->getConfig('enableDeveloperMode')) {
                    $currentThemePath = $this->_websiteConfig['path'] . $this->_themeConfig['path'] . $currentTheme;
                    if(preg_match('/^mobile_/', $listtemplates)) {
                        $currentTemplatePath = $currentThemePath . DIRECTORY_SEPARATOR .'mobile'. DIRECTORY_SEPARATOR. str_replace('mobile_', '', $listtemplates) . '.html';
                    }else{
                        $currentTemplatePath = $currentThemePath . DIRECTORY_SEPARATOR . $listtemplates . '.html';
                    }

                    if (file_exists($currentTemplatePath)) {
                        $themeConfig = Tools_Theme_Tools::getThemeIniData($currentThemePath);
                        $templateName = preg_replace(
                            array('/\\' . DIRECTORY_SEPARATOR . '/', '/\.html/'),
                            array('_', ''),
                            $listtemplates
                        );

                        $template = array(
                            'name'     => $templateName,
                            'fullName' => $templateName,
                            'type'     => (!empty($themeConfig) && isset($themeConfig[$templateName])) ?
                                    $themeConfig[$templateName] : Application_Model_Models_Template::TYPE_REGULAR,
                            'content'  => Tools_Filesystem_Tools::getFile($currentTemplatePath)
                        );

                        $this->_helper->response->response($template, true);
                    }
                } elseif (($template = $mapper->find($listtemplates)) !== null
                    && $template instanceof Application_Model_Models_Template
                ) {
                    $template = array(
                        'name'     => $template->getName(),
                        'fullName' => $template->getName(),
                        'type'     => $template->getType(),
                        'content'  => $template->getContent()
                    );

                    $this->_helper->response->response($template, true);
                } else {
                    $this->_helper->response->response($this->_translator->translate('Template not found'), true);
                }
            }

            exit;
        }
    }

    private function _getTemplateListByType($type, $currentTheme, $currentTemplate = '')
    {
        $templateList = array();

        // Gets the templates list from the current theme folder
        if ((bool)$this->_helper->config->getConfig('enableDeveloperMode')) {
            $currentThemePath = $this->_websiteConfig['path'].$this->_themeConfig['path'].$currentTheme;
            $themeConfig      = Tools_Theme_Tools::getThemeIniData($currentThemePath);
            $scanDir          = Tools_Filesystem_Tools::scanDirectory($currentThemePath);
            $mobileTemplates  = array();
            $pattern          = '/\.html$/i';
            foreach ($scanDir as $file) {
                if ((bool)preg_match($pattern, $file)) {
                    $templateName = preg_replace(
                        array('/\\'.DIRECTORY_SEPARATOR.'/', $pattern),
                        array('_', ''),
                        $file
                    );
                    $templateType = Application_Model_Models_Template::TYPE_REGULAR;
                    if (array_key_exists('mobile_' . $templateName, $themeConfig)
                        && !in_array($templateName, $mobileTemplates)
                    ) {
                        $mobileTemplates[] = $templateName;
                        $templateName = 'mobile_' . $templateName;
                    }

                    if (!empty($themeConfig) && isset($themeConfig[$templateName])) {
                        $templateType = $themeConfig[$templateName];
                    }

                    if ($type != 'all' && $type != $templateType) {
                        continue;
                    }

                    array_push(
                        $templateList,
                        array(
                            'type' => $templateType,
                            'name' => $templateName,
                            'fullName' => $templateName,
                            'isCurrent' => ($templateName == $currentTemplate) ? true : false,
                            'pagesCount' => Tools_Page_Tools::getPagesCountByTemplate($templateName)
                        )
                    );
                }
            }
        } //Gets the templates list from the database
        else {
            $templates = Application_Model_Mappers_TemplateMapper::getInstance()->fetchAll(
                ($type != 'all') ? "type = '$type''" : null
            );

            foreach ($templates as $template) {
                array_push(
                    $templateList,
                    array(
                        'type' => $template->getType(),
                        'name' => $template->getName(),
                        'fullName' => $template->getName(),
                        'isCurrent' => ($template->getName() == $currentTemplate) ? true : false,
                        'pagesCount' => Tools_Page_Tools::getPagesCountByTemplate($template->getName())
                    )
                );
            }
        }

        return $templateList;
    }

    /**
     * Method which delete template (AJAX)
     */
    public function deletetemplateAction()
    {
        if ($this->getRequest()->isPost()) {
            if ($templateId = $this->getRequest()->getPost('id')) {
                $mapper = Application_Model_Mappers_TemplateMapper::getInstance();
                $template = $mapper->find($templateId);
                $currentThemePath = realpath(
                    $this->_websiteConfig['path'] . $this->_themeConfig['path'] . $this->_helper->config->getConfig(
                        'currentTheme'
                    )
                );

                if ($template instanceof Application_Model_Models_Template
                    && !in_array($template->getName(), Tools_Theme_Tools::$protectedTemplates)
                ) {
                    if ($mapper->delete($template)) {
                        if ($template->getType() === Application_Model_Models_Template::TYPE_MOBILE
                            && preg_match('~^mobile_~', $template->getName())
                        ) {
                            $filename = preg_replace(
                                '~^mobile_~',
                                'mobile' . DIRECTORY_SEPARATOR,
                                $template->getName()
                            );
                        } else {
                            $filename = $template->getName();
                        }

                        Tools_Filesystem_Tools::deleteFile(
                            $currentThemePath . DIRECTORY_SEPARATOR . $filename . '.html'
                        );
                        $status = $this->_translator->translate('Template deleted.');
                    } else {
                        $status = $this->_translator->translate('Can\'t delete template or template doesn\'t exists.');
                    }
                    $this->_helper->response->response($status, false);
                } elseif ((bool)$this->_helper->config->getConfig('enableDeveloperMode')) {
                    $templatePath = $currentThemePath . DIRECTORY_SEPARATOR . $templateId . '.html';
                    if (file_exists($templatePath)) {
                        Tools_Filesystem_Tools::deleteFile($templatePath);
                        $this->_helper->response->response($this->_translator->translate('Template deleted.'), false);
                    }
                }
            }
            $this->_helper->response->response($this->_translator->translate('Template doesn\'t exists'), false);
        }
    }

    public function themesAction()
    {
        $this->view->helpSection = 'themes';
    }

    /**
     * @deprecated Use put action of the themes rest-service. Will be removed in 2.0.7
     *
     */
    public function applythemeAction()
    {
    }

    /**
     * @deprecated Use get action of the themes rest-service. Will be removed in 2.0.7
     *
     */
    public function downloadthemeAction()
    {
    }

    /**
     * @deprecated Use delete action of the themes rest-service. Will be removed in 2.0.7
     *
     */
    public function deletethemeAction()
    {
    }

    /**
     * Method saves theme in database
     *
     * @deprecated  Will be removed in 2.0.7
     */
    private function _saveThemeInDatabase($themeName)
    {
        return false;
    }

    /**
     * Returns amount of pages using specific template
     *
     */
    public function pagesviatemplateAction()
    {
        $templateName = $this->getRequest()->getParam('template', '');
        if ($templateName) {
            $this->view->pagesUsingTemplate = Tools_Page_Tools::getPagesCountByTemplate($templateName);
        }
    }

}
