<?php

/**
 * Tools
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Tools_Mail_Tools {

	public static function getMailTemplatesHash() {
		$hash          = array();
		$mailTemplates = Application_Model_Mappers_TemplateMapper::getInstance()->findByType(Application_Model_Models_Template::TYPE_MAIL);
		if(!empty ($mailTemplates)) {
			foreach ($mailTemplates as $temlate) {
				$hash[$temlate->getName()] = ucfirst($temlate->getName());
			}
		}
		return $hash;
	}

}

