<?php

class Widgets_Fbmeta_Fbmeta extends Widgets_Abstract {

	protected function _init() {
		parent::_init();
		array_push($this->_cacheTags, 'pageid_'.$this->_toasterOptions['id']);
	}

	protected function  _load() {
		return $this->_getMetaContent();
	}

	private function _getMetaContent() {
        $websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('Website');
        $configHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('config');
        $configHelper->init();
        $config = $configHelper->getConfig();

        $previewImage = $this->_toasterOptions['previewImage'];
        if (!empty($previewImage)) {
            $previewImage = $this->_toasterOptions['websiteUrl'].$websiteHelper->getPreview().$previewImage;
        } elseif(!empty($config['wicCorporateLogo'])) {
            $previewImage = $this->_toasterOptions['websiteUrl'].$config['wicCorporateLogo'];
        } else {
            $previewImage = $this->_toasterOptions['websiteUrl'];
        }

        return '<meta property="og:title" content="'.$this->_toasterOptions['headerTitle'].'"/>'."\n".
               '<meta property="og:type" content="website"/>'."\n".
               '<meta property="og:url" content="'.$this->_toasterOptions['websiteUrl'].$this->_toasterOptions['url'].'"/>'."\n".
               '<meta property="og:image" content="'.$previewImage.'"/>'."\n".
               '<meta property="og:site_name" content="'.$domain = parse_url($this->_toasterOptions['websiteUrl'], PHP_URL_HOST).'"/>'."\n".
               '<meta property="og:description" content="'.$this->_toasterOptions['metaDescription'].'"/>'."\n";
	}
}
