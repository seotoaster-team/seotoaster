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
//		$this->_translator = Zend_Registry::get('Zend_Translate');
	}

	/**
	 * Returns a list of available translation languages
     * @param $detailed boolean Fetch additional info for translation
	 * @return array List of translations
	 */
	public function getLanguages($detailed = true) {
		$websiteConfigHelper = Zend_Controller_Action_HelperBroker::getExistingHelper('website');
		$languageIcons	     = Tools_Filesystem_Tools::findFilesByExtension($websiteConfigHelper->getPath().$this->_langFlagsDir, 'png', false, true, false);

        $this->_languages = array();

        $loadedList = Zend_Registry::get('Zend_Translate')->getAdapter()->getList();

        foreach ($languageIcons as $country => $imgFile) {
            $locale = new Zend_Locale(Zend_Locale::getLocaleToTerritory($country));
            $lang = $locale->getLanguage();
            $langTitle = Zend_Locale::getTranslation($lang, 'language');

            if (!in_array($locale->getLanguage(), $loadedList)) continue;

            if ($detailed) {
                $this->_languages[$country] = array(
                    'locale'	=> $locale->toString(),
                    'language'	=> $langTitle,
                    'name'		=> $country,
                    'flag'		=> $this->_langFlagsDir . $imgFile
                );
            } else {
                $this->_languages[$country] = $langTitle;
            }

            unset($locale);
        }

		return $this->_languages;
	}

    /**
     * @return string Current website language
     */
	public function getCurrentLanguage(){
		return Zend_Registry::get('Zend_Locale')->getLanguage();
	}

	public function setLanguage($selectedLanguage){
		$sessionHelper = Zend_Controller_Action_HelperBroker::getExistingHelper('session');
        $cacheHelper   = Zend_Controller_Action_HelperBroker::getStaticHelper('cache');

		$locale        = $sessionHelper->locale;
        $newLocale = Zend_Locale::getLocaleToTerritory($selectedLanguage);

        if ($newLocale !== null) {
            $locale->setLocale($newLocale);
        } else {
            if (Zend_Locale::isLocale($selectedLanguage)){
                $locale->setLocale($selectedLanguage);
            }
        }
        $sessionHelper->locale = $locale;
        Zend_Registry::get('Zend_Translate')->setLocale($locale);

        $cacheHelper->clean(false, false, array('locale', 'language'));
		return $locale->getLanguage();
	}

	public function translate($string) {
		return Zend_Registry::get('Zend_Translate')->translate($string);
	}
}