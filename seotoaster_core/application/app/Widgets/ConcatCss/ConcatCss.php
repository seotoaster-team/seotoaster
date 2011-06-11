<?php

/**
 * Description of Concatcss
 *
 * @author iamne
 */
class Widgets_ConcatCss_ConcatCss extends Widgets_Abstract {

	const FILES_EXTENSION   = 'css';

	const FILENAME          = 'concat.css';

	private $_themeFullPath = '';

	private $_excludeFiles  = array(
		self::FILENAME,
		//'reset.css',
		//'style.css',
		//'content.css'
	);
	
	private $_refresh = false;

	protected function  _init() {
		parent::_init();
		if(!empty($this->_toasterOptions)) {
			$this->_themeFullPath = $this->_toasterOptions['themePath'] . $this->_toasterOptions['currentTheme'];
		}
		if(isset($this->_options) && !empty($this->_options)) {
			$this->_refresh = isset($this->_options['refresh']) ? $this->_options['refresh']  : false ;
		}
	}

	private function _addCss($cssPath) {
		$cssContent = '';
		$fileName = explode('/', $cssPath);
		$fileName = strtoupper(end($fileName));
		if(file_exists($cssPath)) {
			$cssContent .= "/**** " .  strtoupper($fileName) . " start ****/\n";
			$cssContent .= preg_replace('~\@charset\s\"utf-8\"\;~Ui', '', file_get_contents($cssPath));
			$cssContent .= "/**** " .  strtoupper($fileName) . " end ****/\n";
		}
		return $cssContent;
	}

	protected function  _load() {
		if(!file_exists($this->_themeFullPath . '/' . self::FILENAME) || $this->_refresh) {
			$concatContent = '';
			$cssFiles = Tools_Filesystem_Tools::findFilesByExtension($this->_themeFullPath, self::FILES_EXTENSION, true);
			
			foreach ($cssFiles as $key => $cssFile) {
				if(in_array(basename($cssFile), $this->_excludeFiles)) {
					continue;
				}
				$concatContent .= $this->_addCss($cssFile);
			}
			try {
				Tools_Filesystem_Tools::saveFile($this->_themeFullPath . '/' . self::FILENAME , $concatContent);
			}
			catch (Exceptions_SeotoasterException $ste) {
				return $ste->getMessage();
			}
		}
		return '<link href="' . $this->_toasterOptions['websiteUrl'] . $this->_themeFullPath . '/' .  self::FILENAME . '" rel="stylesheet" type="text/css" media="screen" />';
	}
}

