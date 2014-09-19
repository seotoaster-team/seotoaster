<?php

class Widgets_Template_Template extends Widgets_Abstract
{
    const TEMPLATE_TYPE = 'type_partial_template';

    protected function  _load()
    {
        $templateName = array_shift($this->_options);
        $template = Application_Model_Mappers_TemplateMapper::getInstance()->find($templateName);
        if ($template !== null) {
            if($template->getType() === self::TEMPLATE_TYPE) {
                return $template->getContent();
            } else {
                return '<span style="color: red;">Choose \'Partial template\' type</span>';
            }
        } else {
            return '<span style="color: red;">No template with name "' . $templateName . '"</span>';
        }
    }
}