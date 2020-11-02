<?php

/**
 *
 */
class Widgets_Pagebreak_Pagebreak extends Widgets_Abstract
{

    protected $_cacheable = false;

    protected $_websiteHelper = null;

    protected $_sessionHelper = null;

    protected $_translator = null;

    protected function _init()
    {
        $this->_websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
        $this->_sessionHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('session');
        $this->_translator = Zend_Controller_Action_HelperBroker::getStaticHelper('language');
        $this->_view = new Zend_View(array('scriptPath' => __DIR__ . '/views'));
    }

    protected function _load()
    {
        return $this->_view->render('pagebreak.phtml');
    }

}

