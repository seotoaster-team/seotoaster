<?php

class Widgets_Template_Template extends Widgets_Abstract
{
    const TEMPLATE_TYPE = 'type_partial_template';

    protected $_config = null;

    protected function  _load()
    {
        $templateName  = array_shift($this->_options);
        $this->_config = Zend_Controller_Action_HelperBroker::getStaticHelper('config');

        // if developerMode = 1, parsing template directly from files
        if ((bool) $this->_config->getConfig('enableDeveloperMode')) {
            $websitePath  = $this->_toasterOptions['websitePath'];
            $themePath    = $this->_toasterOptions['themePath'];
            $currentTheme = $this->_toasterOptions['currentTheme'];
            $templatePath = $websitePath.$themePath.$currentTheme.DIRECTORY_SEPARATOR.$templateName.'.html';
            if (file_exists($templatePath)) {
                return Tools_Filesystem_Tools::getFile($templatePath);
            } else {
                return '<span style="color: red;">No template with name "' . $templateName . '"</span>';
            }
        }

        $template = Application_Model_Mappers_TemplateMapper::getInstance()->find($templateName);
        if ($template !== null) {
            if($template->getType() === self::TEMPLATE_TYPE) {
                return $template->getContent();
            } else {
                return '<span style="color: red;">Choose \'Nested Template\' type</span>';
            }
        } else {
            return '<span style="color: red;">No template with name "' . $templateName . '"</span>';
        }
    }
}