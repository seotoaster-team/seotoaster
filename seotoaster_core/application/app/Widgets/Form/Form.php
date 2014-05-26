<?php

class Widgets_Form_Form extends Widgets_Abstract {

    const WFORM_CACHE_TAG = 'formWidget';

    const UPLOAD_LIMIT_SIZE = 10;

    const WITHOUT_CACHE = 'withoutcache';

	private $_websiteHelper   = null;

	protected function _init() {
		parent::_init();
		$this->_view = new Zend_View(array(
			'scriptPath' => dirname(__FILE__) . '/views'
		));
		$this->_websiteHelper    = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
		$this->_view->websiteUrl = $this->_websiteHelper->getUrl();

		if (is_array($this->_options) && isset($this->_options[1])) {
			$this->_cacheable = !($this->_options[1] === 'recaptcha' || $this->_options[1] === 'captcha');
		}
        if (is_array($this->_options) && isset($this->_options[0]) && strtolower($this->_options[0]) === 'conversioncode') {
			$this->_cacheable = false;
		}
        if(is_array($this->_options) && isset($this->_options[0]) && in_array(self::WITHOUT_CACHE, $this->_options)){
            $this->_cacheable = false;
        }
        $this->_cacheTags = array(self::WFORM_CACHE_TAG);
        Zend_Layout::getMvcInstance()->getView()->headScript()->appendFile(
            $this->_websiteHelper->getUrl() . 'system/js/external/sisyphus/sisyphus.min.js'
        );
    }
    protected function _load() {
		if(!is_array($this->_options) || empty($this->_options) || !isset($this->_options[0]) || !$this->_options[0] || preg_match('~^\s*$~', $this->_options[0])) {
			throw new Exceptions_SeotoasterException($this->_translator->translate('You should provide a form name.'));
		}
        
        if(strtolower($this->_options[0]) == 'conversioncode'){
            return $this->_conversionCode($this->_options);
        }
        $recaptchaStyle = 'custom';
        $buttonLabel = "Send";
        $sessionHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('Session');

		$useCaptcha   = (isset($this->_options[1]) && $this->_options[1] == 'captcha') ? true : false;
        $useRecaptcha = (isset($this->_options[1]) && $this->_options[1] == 'recaptcha') ? true : false;
        if($useRecaptcha && isset($this->_options[2])){
            $recaptchaStyle = $this->_options[2];
        }
        if(isset($this->_options[3])){
            //recaptcha exist
            $buttonLabel = $this->_options[3];
        } elseif(isset($this->_options[1]) && $this->_options[1] != "recaptcha" && $this->_options[1] != ""){
            //no recaptcha but keep the value for submit button
            $buttonLabel = $this->_options[1];
        }


        $uploadLimitSize = (is_numeric(end($this->_options)) ? end($this->_options) : self::UPLOAD_LIMIT_SIZE);
        $formMapper   = Application_Model_Mappers_FormMapper::getInstance();
        $pageMapper   = Application_Model_Mappers_PageMapper::getInstance(); 
        $form         = $formMapper->findByName($this->_options[0]);
        $pageHelper = new Helpers_Action_Page();
        $pageHelper->init();

        $captchaStatus = 0;
		if($useCaptcha || $useRecaptcha) {
            $captchaStatus = 1;
            if($useRecaptcha){
                $recaptchaTheme = $recaptchaStyle;
                $recaptchaWidgetId = uniqid('recaptcha_widget_');
                if(isset($this->_options[2])){
                    $recaptchaTheme = $this->_options[2];
                    if($recaptchaTheme == 'custom'){
                        $this->_view->customRecaptcha = true;
                    }
                }
                $this->_view->recaptchaWidgetId = $recaptchaWidgetId;
                $this->_view->addScriptPath($this->_websiteHelper->getPath()
                    . 'seotoaster_core/application/views/scripts/backend/form/');
                $this->_view->recaptchaCode = Tools_System_Tools::generateRecaptcha($recaptchaTheme, $recaptchaWidgetId);
            }
            if($useCaptcha){
                $this->_view->captchaId = Tools_System_Tools::generateCaptcha();
            }
		}
        if($form != null){
            $form->setCaptcha($captchaStatus);
            $formMapper->save($form);
        }
        if(isset($sessionHelper->toasterFormError)){
            $this->_view->toasterFormError = $sessionHelper->toasterFormError;
            unset($sessionHelper->toasterFormError);
        }
        if(isset($sessionHelper->toasterFormSuccess)){
            $this->_view->toasterFormSuccess = $sessionHelper->toasterFormSuccess;
            unset($sessionHelper->toasterFormSuccess);
        }
        $trackingConversionUrl = 'form-'.$this->_options[0].'-thank-you';
        $trackingConversionUrl = $pageHelper->filterUrl($trackingConversionUrl);
        $trackingPageExist = $pageMapper->findByUrl($trackingConversionUrl);
        if($trackingPageExist instanceof Application_Model_Models_Page){
            $this->_view->trackingConversionUrl = $trackingConversionUrl;
        }
     	$this->_view->useRecaptcha      = $useRecaptcha;
        $this->_view->useCaptcha        = $useCaptcha;
		$this->_view->form              = Application_Model_Mappers_FormMapper::getInstance()->findByName($this->_options[0]);
		$this->_view->allowMidification = Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_CONTENT);
		$this->_view->formName          = $this->_options[0];
        $this->_view->uploadLimitSize   = $uploadLimitSize;

        $filter                         = new Zend_Filter_Alnum();
        $this->_view->formId            = $filter->filter($this->_options[0]);
        $this->_view->pageId            = $this->_toasterOptions['id'];
		$this->_view->websiteTmp        = $this->_websiteHelper->getTmp();
        $this->_view->formUrl           = $this->_toasterOptions['url'];
        $this->_view->buttonValue       = $buttonLabel;
		return $this->_view->render('form.phtml');
	}

    private function _conversionCode($options){
        $sessionHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('Session');
        $trackingCode = '';
        if(isset($sessionHelper->formName) && isset($sessionHelper->formPageId)){
            $formName   = $sessionHelper->formName;
            $formPageId = $sessionHelper->formPageId;
            $conversionCode = Application_Model_Mappers_FormPageConversionMapper::getInstance()->getConversionCode($formName, $formPageId);
            if(!empty($conversionCode)){
                $trackingCode = $conversionCode[0]->getConversionCode();
            }
            unset($sessionHelper->formName);
            unset($sessionHelper->formPageId);
            
        }
        return $trackingCode;
        
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