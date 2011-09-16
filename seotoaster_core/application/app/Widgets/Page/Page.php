<?php
/**
 * $page widget
 *
 * @author iamne
 */
class Widgets_Page_Page extends Widgets_Abstract {


	protected function  _load() {
		$optionMakerName = '_generate' . ucfirst($this->_options[0]) . 'Option';
		if(method_exists($this, $optionMakerName)) {
			return $this->$optionMakerName();
		}
		return 'Wrong widget option: <strong>' . $this->_options[0] . '</strong>';
	}

	private function _generateIdOption() {
		return $this->_toasterOptions['id'];
	}

	private function _generateH1Option() {
		return $this->_toasterOptions['h1'];
	}

	private function _generateTitleOption() {
		return $this->_toasterOptions['headerTitle'];
	}

	private function _generateTeaserOption() {
		return $this->_toasterOptions['teaserText'];
	}

	private function _generatePreviewOption() {
		$websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('Website');
		$pageHelper    = Zend_Controller_Action_HelperBroker::getStaticHelper('Page');
 		$files         = Tools_Filesystem_Tools::findFilesByExtension($websiteHelper->getPath() . $websiteHelper->getPreview(), '(jpg|gif|png|jpeg)', false, false, false);
		$pagePreviews  = array_values(preg_grep('/^' . $pageHelper->clean($this->_toasterOptions['url']) . '\.(png|jpg|gif|jpeg)$/', $files));

		if(!empty ($pagePreviews)) {
			return '<a href="' . $websiteHelper->getUrl() . $this->_toasterOptions['url'] . '" title="' . $this->_toasterOptions['h1'] . '"><img src="' . $websiteHelper->getUrl() . $websiteHelper->getPreview() . $pagePreviews[0] . '" alt="'  . $pageHelper->clean($this->_toasterOptions['url']) . '" /></a>';
		}
		return;
	}

	public static function getAllowedOptions() {
		return array('page:id', 'page:h1', 'page:title', 'page:preview', 'page:teaser');
	}

}

