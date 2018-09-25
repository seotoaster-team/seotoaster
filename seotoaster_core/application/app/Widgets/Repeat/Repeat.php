<?php

/**
 * Repeat widgets for {repeat} magic space
 */
class Widgets_Repeat_Repeat extends Widgets_Abstract
{

    /**
     * Static repeat
     */
    const REPEAT_STATIC = 'static';

    /**
     * Option quantity
     */
    const REPEAT_QUANTITY_OPTION = 'quantity';

    /**
     * Repeat order option
     */
    const REPEAT_ORDER_OPTION = 'order';

    protected $_cacheable      = false;

    protected function  _init()
    {
        parent::_init();
        $this->_view = new Zend_View(array(
            'scriptPath' => dirname(__FILE__) . '/views'
        ));
        $website = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
        $this->_view->websiteUrl = $website->getUrl();
    }

    protected function _load()
    {
        if (empty($this->_options[0])) {
            throw new Exceptions_SeotoasterWidgetException('Missed repeat name');
        }

        if (empty($this->_options[1])) {
            throw new Exceptions_SeotoasterWidgetException('Missed repeat option');
        }

        $static = array_search(self::REPEAT_STATIC, $this->_options);

        $type = Application_Model_Models_Container::TYPE_REGULARCONTENT;
        if ($static !== false) {
            $type = Application_Model_Models_Container::TYPE_STATICCONTENT;
        }

        $mapper = Application_Model_Mappers_ContainerMapper::getInstance();
        $pageId = $this->_toasterOptions['id'];
        $name = MagicSpaces_Repeat_Repeat::PREFIX_CONTAINER . $this->_options[0];
        $containerModel = $mapper->findByName($name, $pageId, $type);

        if ($containerModel instanceof Application_Model_Models_Container) {
            $content = explode(':', $containerModel->getContent());
            if (!empty($content[0]) && $this->_options[1] === self::REPEAT_QUANTITY_OPTION) {
                return (int)$content[0];
            }
            if (!empty($content[1]) && $this->_options[1] === self::REPEAT_ORDER_OPTION) {
                return $content[1];
            }
        } elseif(!empty($this->_options[2]) && $this->_options[1] === self::REPEAT_QUANTITY_OPTION) {
            return (int)$this->_options[2];
        }

        return '';
    }


}
