<?php

class Widgets_Template_Template extends Widgets_Abstract {

	protected function  _load() {
		$templateName   = array_shift($this->_options);
        $template = Application_Model_Mappers_TemplateMapper::getInstance()->find($templateName);
        return ($template !== null) ? $template->getContent() : '';
	}
}