<?php

/**
 * Language
 *
 * @author Pavel Kovalyov <pavlo.kovalyov@gmail.com>
 */
class Helpers_Action_Language extends Zend_Controller_Action_Helper_Abstract {

	private $_translator   = null;

	private $_languages	   = array();

	private $_langFlagsDir = 'system/images/flags/';

	public function  init() {
		$this->_translator = Zend_Registry::get('Zend_Translate');
	}

	/**
	 * Returns a list of available translation languages
	 * @return array
	 */
	public function getLanguages($detailed = true) {
		$websiteConfigHelper = Zend_Controller_Action_HelperBroker::getExistingHelper('website');
		$languageDir	     = $websiteConfigHelper->getPath() . $websiteConfigHelper->getLang();
		$languageFiles	     = Tools_Filesystem_Tools::findFilesByExtension($languageDir, 'lng', false, true, false);
		$languageIcons	     = Tools_Filesystem_Tools::findFilesByExtension($websiteConfigHelper->getPath().$this->_langFlagsDir, 'png', false, true, false);

		$this->_languages = array_diff($languageFiles, $languageIcons);
		array_walk($this->_languages, function(&$file, $lang, $data){
			if (Zend_Locale::isLocale($lang)) {
				if ($data['detailed']) {
					$file = array(
						'locale'	=> $lang,
						'language'	=> Zend_Locale::getTranslation($lang, 'language'),
						'name'		=> $file,
						'flag'		=> $data['flagPath'] . $data['flags'][$lang]
					);
				} else {
					$file = Zend_Locale::getTranslation($lang, 'language');
				}
			}
			return $file;
		}, array(
			'flagPath'	=> $this->_langFlagsDir,
			'flags'		=> $languageIcons,
			'detailed'	=> $detailed
		));

		return $this->_languages;
	}

	public function getCurrentLanguage(){
		$currentLocale = $this->_translator->getAdapter()->getLocale();
		return $currentLocale;
	}

	public function setLanguage($lang){
		$sessionHelper = Zend_Controller_Action_HelperBroker::getExistingHelper('session');
		$locale        = $sessionHelper->locale;
		if (Zend_Locale::isLocale($lang)){
			$locale->setLocale($locale->getLocaleToTerritory($lang));
			$sessionHelper->locale = $locale;
			$this->_translator->getAdapter()->setLocale($lang);
		}
		return $this->_translator->getAdapter()->getLocale();
	}

	public function translate($string) {
		return $this->_translator->translate($string);
	}
}