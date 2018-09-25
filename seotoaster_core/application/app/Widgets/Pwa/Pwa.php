<?php

class Widgets_Pwa_Pwa extends Widgets_Abstract {

    protected function _init()
    {
        parent::_init();
        $this->_view = new Zend_View(array('scriptPath' => __DIR__ . '/views'));
    }

    protected function  _load() {
        $optionMakerName = '_generate' . ucfirst($this->_options[0]) . 'Option';
        if(method_exists($this, $optionMakerName)) {
            return $this->$optionMakerName();
        }
		return 'Wrong widget option: <strong>' . $this->_options[0] . '</strong>';
	}

	/*
	 * Option that renders "link" tag if manifest file exists.
	 */
    private function _generateManifestOption()
    {
        if (file_exists('manifest.json')) {
           return '<link rel="manifest" href="manifest.json">';
        }
        return '';
    }

    /*
     * Option that loads "service worker" if both sw.js and manifest.json files exist.
     * Service worker view surrounded by "notadmin" magic-space, so it won't be loaded if you are logged-in as admin/superadmin
     */
	private function _generateSwOption() {
        if (file_exists('sw.js') && file_exists('manifest.json')) {
            return $this->_view->render('sw.phtml');;
        }
        return '';
	}

    /*
     * Option that renders add to home screen button and attaches install event to it.
     */
	private function _generateA2hsOption() {
        $this->_view->buttonText = !empty($this->_options[1]) ? filter_var($this->_options[1], FILTER_SANITIZE_STRING) : $this->_translator->translate('Install App');
        return $this->_view->render('a2hs.phtml');
	}

    /*
     * Option that disables add to home screen banner.
     */
    private function _generateDisableOption() {
        return $this->_view->render('disable.phtml');
    }

}

