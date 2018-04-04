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

    private function _generateManifestOption()
    {
        if (file_exists('manifest.json')) {
           return '<link rel="manifest" href="manifest.json">';
        }
        return '';
    }

	private function _generateSwOption() {
        if (file_exists('sw.js') && file_exists('manifest.json')) {
            return $this->_view->render('sw.phtml');;
        }
        return '';
	}

}

