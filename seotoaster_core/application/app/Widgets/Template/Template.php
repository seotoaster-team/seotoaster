<?php

class Widgets_Template_Template extends Widgets_Abstract
{
    /**
     * Partial template type (nested templates)
     */
    const TEMPLATE_TYPE = 'type_partial_template';

    /**
     * Disable cache option
     */
    const WITHOUT_CACHE = 'without-cache';

    /**
     * Parse partial template before returning into the main parser
     */
    const PRE_PARSE_TEMPLATE = 'pre-parse';

    protected function _init()
    {

        if (array_search(self::WITHOUT_CACHE, $this->_options) !== false) {
            $this->_cacheable = false;
        }
    }

    protected function  _load()
    {
        $templateName  = array_shift($this->_options);
        $websitePath  = $this->_toasterOptions['websitePath'];
        $themePath    = $this->_toasterOptions['themePath'];
        $currentTheme = $this->_toasterOptions['currentTheme'];
        $missingTemplate = '<span style="color: red;">No template with name "' . $templateName . '"</span>';

        $preParse = false;
        if (array_search('pre-parse', $this->_options) !== false) {
            $preParse = true;
        }

        // if developerMode = 1, parsing template directly from files
        if ($this->_developerModeStatus) {
            $templatePath = $websitePath.$themePath.$currentTheme.DIRECTORY_SEPARATOR.$templateName.'.html';
            if (file_exists($templatePath)) {
                $content =  Tools_Filesystem_Tools::getFile($templatePath);
                if ($preParse) {
                    $content = $this->_preParseTemplate($content, $websitePath, $currentTheme, $themePath);
                }
            } else {
                $content = $missingTemplate;
            }
        } else {
            $template = Application_Model_Mappers_TemplateMapper::getInstance()->find($templateName);
            if ($template !== null) {
                if ($template->getType() === self::TEMPLATE_TYPE) {
                    $content = $template->getContent();
                    if ($preParse) {
                        $content = $this->_preParseTemplate($content, $websitePath, $currentTheme, $themePath);
                    }
                } else {
                    $content = '<span style="color: red;">Choose \'Nested Template\' type</span>';
                }
            } else {
                $content = $missingTemplate;
            }
        }

        return $content;
    }

    /**
     * Pre-parse partial template content
     *
     * @param string $content partial template content
     * @param string $websitePath website path
     * @param string $currentTheme current theme name
     * @param string $themePath theme path
     * @return null
     */
    private function _preParseTemplate($content, $websitePath, $currentTheme, $themePath)
    {
        $parserOptions = array(
            'websiteUrl' => $this->_toasterOptions['websiteUrl'],
            'websitePath' => $websitePath,
            'currentTheme' => $currentTheme,
            'themePath' => $themePath,
        );
        $parser = new Tools_Content_Parser(' '.$content, $this->_toasterOptions, $parserOptions);

        return $parser->parseSimple();
    }

}