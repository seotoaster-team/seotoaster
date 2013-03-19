<?php

class Widgets_Form_Form extends Widgets_Abstract {

    const WFORM_CACHE_TAG = 'formWidget';

	private $_websiteHelper   = null;

	protected function _init() {
		parent::_init();
		$this->_view = new Zend_View(array(
			'scriptPath' => dirname(__FILE__) . '/views'
		));
		$this->_websiteHelper    = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
		$this->_view->websiteUrl = $this->_websiteHelper->getUrl();

		if (is_array($this->_options) && isset($this->_options[1]) && $this->_options[1] === 'recaptcha') {
			$this->_cacheable = false;
		}
        $this->_cacheTags = array(self::WFORM_CACHE_TAG);
    }
    protected function _load() {
		if(!is_array($this->_options) || empty($this->_options) || !isset($this->_options[0]) || !$this->_options[0] || preg_match('~^\s*$~', $this->_options[0])) {
			throw new Exceptions_SeotoasterException($this->_translator->translate('You should provide a form name.'));
		}
        
		$useCaptcha = (isset($this->_options[1]) && $this->_options[1] == 'recaptcha') ? true : false;
        $formMapper = Application_Model_Mappers_FormMapper::getInstance();
        $form       = $formMapper->findByName($this->_options[0]);
                
		if($useCaptcha) {
			if($form != null){
                $form->setCaptcha(1);
                $formMapper->save($form);
            }
            $recaptchaTheme = 'red';
            if(isset($this->_options[2])){
                $recaptchaTheme = $this->_options[2];
                if($this->_options[2] == 'custom'){
                    $this->_view->customRecaptcha = true;
                }
            }
            $this->_view->recapthaCode = Tools_System_Tools::generateRecaptcha($recaptchaTheme);
		}
		$this->_view->useCaptcha        = $useCaptcha;
		$this->_view->form              = Application_Model_Mappers_FormMapper::getInstance()->findByName($this->_options[0]);
		$this->_view->allowMidification = Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_ADMINPANEL);
		$this->_view->formName          = $this->_options[0];

        $filter                         = new Zend_Filter_Alnum();
        $this->_view->formId            = $filter->filter($this->_options[0]);

		$this->_view->websiteTmp        = $this->_websiteHelper->getTmp();
        $this->_view->formUrl           = $this->_toasterOptions['url'];
		return $this->_view->render('form.phtml');
	}

	public static function getWidgetMakerContent() {
		$translator = Zend_Registry::get('Zend_Translate');
		$view       = new Zend_View(array(
				'scriptPath' => dirname(__FILE__) . '/views'
		));

		$websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
		$data = array(
			'title' => $translator->translate('Forms'),
			'content' => $view->render('wmcontent.phtml'),
			'icons'   => array(
				$websiteHelper->getUrl() . 'system/images/widgets/form.png',
			)
		);

		unset($view);
		unset($translator);
		return $data;
	}

}