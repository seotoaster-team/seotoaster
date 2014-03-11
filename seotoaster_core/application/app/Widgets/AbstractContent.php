<?php
abstract class Widgets_AbstractContent extends Widgets_Abstract {

    const OPTION_READONLY   = 'readonly';

	protected $_acl         = null;

	protected $_type        = null;

	protected $_pageId      = null;

	protected $_name        = null;

    protected $_container   = null;

	protected $_cachePrefix = 'content_';

    protected function _generateAdminControl($width = 0, $height = 0, $hint = '') {
        if (end($this->_options) == self::OPTION_READONLY) {
            return false;
        }
        $controlIcon = 'editadd';

        if(in_array('static', $this->_options)) {
            $controlIcon .= '-static';
        }

        $widgetName   = explode('_', get_called_class());
        $widgetName   = strtolower(end($widgetName));
        $controlIcon .= '-' . $widgetName . '.png';
        if(!$hint) {
            $hint = 'edit ' . ($widgetName == 'content' ? '' : $widgetName) . ' content';
        }

        $containerId = ($this->_container !== null) ? $this->_container->getId() : null;
        if($containerId) {
            return '<a class="tpopup generator-links" data-pwidth="' . $width . '" data-pheight="' . $height . '" title="Click to ' . $hint . '" href="javascript:;" data-url="' . $this->_toasterOptions['websiteUrl'] . 'backend/backend_content/edit/id/' . $containerId . '/containerType/' . $this->_type . '"><img width="26" height="26" src="' . $this->_toasterOptions['websiteUrl'] . 'system/images/' . $controlIcon .'" alt="'. $hint .'" /></a>';
        }
        return '<a class="tpopup generator-links" data-pwidth="' . $width . '" data-pheight="' . $height . '" title="Click to ' . $hint . '" href="javascript:;" data-url="' . $this->_toasterOptions['websiteUrl'] . 'backend/backend_content/add/containerType/' . $this->_type . '/containerName/' . $this->_name . '/pageId/' . $this->_toasterOptions['id'] . '"><img width="26" height="26" src="' . $this->_toasterOptions['websiteUrl'] . 'system/images/' . $controlIcon . '" alt="'. $hint .'" /></a>';
    }

	protected function _init() {
		parent::_init();
		$this->_name    = $this->_options[0];
		$this->_pageId  = ($this->_type == Application_Model_Models_Container::TYPE_STATICCONTENT || $this->_type == Application_Model_Models_Container::TYPE_STATICHEADER || $this->_type == Application_Model_Models_Container::TYPE_PREPOPSTATIC) ? 0 : $this->_toasterOptions['id'];
        $contentId = $this->_name .'_'. $this->_type .'_pid_'. $this->_pageId;

        $separator = '_';
        if (in_array('readonly', $this->_options)) {
            $separator = '_readonly_';
        }

		array_push($this->_cacheTags, preg_replace('/[^\w\d_]/', '', $contentId));
		$this->_cacheId = $contentId .$separator. Zend_Controller_Action_HelperBroker::getStaticHelper('Session')->getCurrentUser()->getRoleId();
	}

    protected function _find() {
        if(!isset($this->_toasterOptions['containers'])) {
            return null;
        }

        $containers   = $this->_toasterOptions['containers'];
        $containerKey = md5(implode('-', array($this->_name, ($this->_pageId === null) ? 0 : $this->_pageId, $this->_type)));

        if(!array_key_exists($containerKey, $containers)) {
            return null;
        }

        $container = $containers[$containerKey];

        if((($container['page_id'] == $this->_pageId) || ($container['page_id'] === null)) && $container['container_type'] == $this->_type) {
            $widget      = new Application_Model_Models_Container();
            $widget->setName($this->_name)
                ->setOptions($container);
            return $widget;
        }
        return null;
    }
}

