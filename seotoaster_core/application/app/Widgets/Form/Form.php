<?php

class Widgets_Form_Form extends Widgets_Abstract {

	private $_websiteHelper  = null;

	protected function _init() {
		parent::_init();
		$this->_view = new Zend_View(array(
			'scriptPath' => dirname(__FILE__) . '/views'
		));
		$this->_websiteHelper    = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
		$this->_view->websiteUrl = $this->_websiteHelper->getUrl();

    }
    protected function _load() {
		if(!is_array($this->_options) || empty($this->_options) || !isset($this->_options[0]) || !$this->_options[0] || preg_match('~^\s*$~', $this->_options[0])) {
			throw new Exceptions_SeotoasterException($this->_translator->translate('You should provide a form name.'));
		}

		$useCaptcha = (isset($this->_options[1]) && $this->_options[1] == 'captcha') ? true : false;
		if($useCaptcha) {
			$this->_view->captchaId  = Tools_System_Tools::generateCaptcha();
		}
		$this->_view->useCaptcha        = $useCaptcha;
		$this->_view->form              = Application_Model_Mappers_FormMapper::getInstance()->findByName($this->_options[0]);
		$this->_view->allowMidification = Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_ADMINPANEL);
		$this->_view->formName          = $this->_options[0];
		$this->_view->websiteTmp        = $this->_websiteHelper->getTmp();
		return $this->_view->render('form.phtml');
	}

	public static function getWidgetMakerContent() {
		$translator = Zend_Registry::get('Zend_Translate');
		$view       = new Zend_View(array(
				'scriptPath' => dirname(__FILE__) . '/views'
		));

		$data = array(
			//'title' => $translator->translate('Forms'),
			'content' => $view->render('wmcontent.phtml')
		);

		unset($view);
		unset($translator);
		return $data;
	}

}