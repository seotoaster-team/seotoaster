<?php
/**
 * FormController
 *
 * @author Seotoaser Dev Team
 */
class Backend_FormController extends Zend_Controller_Action {

    const FORM_THANKYOU_PAGE = 'option_formthankyoupage';
    
	public static $_allowedActions = array(
		'receiveform',
        'refreshcaptcha'
	);

    public function init() {
		parent::init();
		if(!Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_CONTENT) && !Tools_Security_Acl::isActionAllowed()) {
			$this->_redirect($this->_helper->website->getUrl(), array('exit' => true));
		}
    	$this->view->websiteUrl = $this->_helper->website->getUrl();
        $this->_helper->AjaxContext()->addActionContexts(array(
			'manageform'  => 'json',
			'delete'  => 'json',
			'loadforms'   => 'json',
			'receiveform' => 'json'
		))->initContext('json');
    }

    public function manageformAction() {
		$formForm = new Application_Form_Form();
        $formPageConversionMapper = Application_Model_Mappers_FormPageConversionMapper::getInstance();
        $pageMapper = Application_Model_Mappers_PageMapper::getInstance();
		if($this->getRequest()->isPost()) {
			if($formForm->isValid($this->getRequest()->getParams())) {
                $formPageConversionModel = new Application_Model_Models_FormPageConversion();
                $formData = $this->getRequest()->getParams();
				$form = new Application_Model_Models_Form($this->getRequest()->getParams());
                $contactEmail = $form->getContactEmail();
                $validEmail = $this->validateEmail($contactEmail);
                if(isset($validEmail['error'])){
                    $this->_helper->response->fail(Tools_Content_Tools::proccessFormMessagesIntoHtml(array('contactEmail'=>$validEmail['error']), get_class($formForm)));
                }
                if(isset($formData['thankyouTemplate']) && $formData['thankyouTemplate'] != 'select'){
                    $trackingPageUrl = $this->_createTrackingPage($formData['name'], $formData['thankyouTemplate']);
                }
                $this->_addConversionCode();
                $formPageConversionModel->setFormName($formData['name']);
                $formPageConversionModel->setPageId($formData['pageId']);
                $formPageConversionModel->setConversionCode($formData['trackingCode']);
                $formPageConversionMapper->save($formPageConversionModel);
                Application_Model_Mappers_FormMapper::getInstance()->save($form);
                $this->_helper->cache->clean('', '', array(Widgets_Form_Form::WFORM_CACHE_TAG));
				$this->_helper->response->success($this->_helper->language->translate('Form saved'));
			}
			else {
				$this->_helper->response->fail(Tools_Content_Tools::proccessFormMessagesIntoHtml($formForm->getMessages(), get_class($formForm)));
			}
		}
		$formName           = filter_var($this->getRequest()->getParam('name'), FILTER_SANITIZE_STRING);
        $pageId             = $this->getRequest()->getParam('pageId');
        $trackingPageName   = 'form-'.$formName.'-thank-you';
        $trackingPageUrl    = $this->_helper->page->filterUrl($trackingPageName);
        $trackingPageExist  = $pageMapper->findByUrl($trackingPageUrl);
        if(!empty($trackingPageExist)){
            $trackingPageResultUrl = $trackingPageUrl;
        }
		$form          = Application_Model_Mappers_FormMapper::getInstance()->findByName($formName);
		$mailTemplates = Tools_Mail_Tools::getMailTemplatesHash();
        $regularPageTemplates = Application_Model_Mappers_TemplateMapper::getInstance()->findByType(Application_Model_Models_Template::TYPE_REGULAR);
        $conversionCode = $formPageConversionMapper->getConversionCode($formName, $pageId);
        if(!empty($conversionCode)){
            $formForm->getElement('trackingCode')->setValue($conversionCode[0]->getConversionCode());
        }
		$formForm->getElement('name')->setValue($formName);
		$formForm->getElement('replyMailTemplate')->setMultioptions(array_merge(array(0 => 'select template'), $mailTemplates));
		if($form !== null) {
			$formForm->populate($form->toArray());
		}
        $this->view->trackingPageUrl = $trackingPageResultUrl;
        $this->view->regularTemplates = $regularPageTemplates;
        $this->view->pageId = $pageId;
		$this->view->formForm = $formForm;
	}

    public function validateEmail($emails){
        $emailValidation = new Zend_Validate_EmailAddress();
        if(is_string($emails) && preg_match('~,~', $emails)){
            $contanctEmails = explode(',',$emails);
            foreach($contanctEmails as $email){
                if(!$emailValidation->isValid(str_replace(" ",'',$email))){
                    return array('error'=>$emailValidation->getErrors());       
                }
            }
        }elseif(is_string($emails) && !$emailValidation->isValid($emails)){
            return array('error'=>$emailValidation->getErrors());
        }
    }
    
    public function deleteAction() {
        $id         = $this->getRequest()->getParam('id');
        $formMapper = Application_Model_Mappers_FormMapper::getInstance();

        //needs to go to the garbage collector in future
        $this->_helper->cache->clean('', '', array(Widgets_Form_Form::WFORM_CACHE_TAG));

        return $formMapper->delete($formMapper->find($id));
    }

	public function loadformsAction() {
		if($this->getRequest()->isPost()) {
			$formsNames = array();
			$mapper     = Application_Model_Mappers_FormMapper::getInstance();
			$forms      = $mapper->fetchAll();
			foreach ($forms as $form) {
				$formsNames[] = $form->getName();
			}
			$this->view->formsNames = $formsNames;
		}
	}

    public function receiveformAction(){
        if($this->getRequest()->isPost()) {
            $formParams    = $this->getRequest()->getParams();
            $sessionHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('Session');
			if(!empty ($formParams)) {
                $websiteConfig = Zend_Controller_Action_HelperBroker::getExistingHelper('config')->getConfig();
                $formMapper = Application_Model_Mappers_FormMapper::getInstance();
                // get the form details
				$form   = $formMapper->findByName($formParams['formName']);
                $useCaptcha = $form->getCaptcha();
                
                //validating recaptcha
                if($useCaptcha == 1){
                    if(!empty($websiteConfig) && isset($websiteConfig['recapthaPublicKey']) && $websiteConfig['recapthaPublicKey'] != '' 
                            && isset($websiteConfig['recapthaPrivateKey']) && $websiteConfig['recapthaPrivateKey'] != '' 
                            && isset($formParams['recaptcha_challenge_field']) || isset($formParams['captcha'])){
                        
                        if(isset($formParams['recaptcha_challenge_field']) && isset($formParams['recaptcha_response_field'])) {
                            if($formParams['recaptcha_response_field'] == ''){
                                $this->_helper->response->fail($this->_helper->language->translate('You\'ve entered an incorrect security text. Please try again.'));
                            }
                            $recaptcha = new Zend_Service_ReCaptcha($websiteConfig['recapthaPublicKey'], $websiteConfig['recapthaPrivateKey']);
                            $result = $recaptcha->verify($formParams['recaptcha_challenge_field'], $formParams['recaptcha_response_field']);
                            if(!$result->isValid()){
                                $this->_helper->response->fail($this->_helper->language->translate('You\'ve entered an incorrect security text. Please try again.'));
                            }
                            unset($formParams['recaptcha_challenge_field']);
                            unset($formParams['recaptcha_response_field']);
                        }else{
                            //validating captcha
                            if(!$this->_validateCaptcha(strtolower($formParams['captcha']), $formParams['captchaId'])) {
                                $this->_helper->response->fail($this->_helper->language->translate('You\'ve entered an incorrect security text. Please try again.'));
                            }
                        }
                    }else{
                        $this->_helper->response->fail($this->_helper->language->translate('You\'ve entered an incorrect security text. Please try again.'));
                    }
                                   
                }
                
                $sessionHelper->formName   = $formParams['formName'];
                $sessionHelper->formPageId = $formParams['formPageId'];
				unset($formParams['formPageId']);               
                //$mailer = Tools_Mail_Tools::initMailer();

				// sending mails
                $sysMailWatchdog = new Tools_Mail_SystemMailWatchdog(array(
                    'trigger'  => Tools_Mail_SystemMailWatchdog::TRIGGER_FORMSENT,
                    'data'     => $formParams
                ));
                $mailWatchdog = new Tools_Mail_Watchdog(array(
                    'trigger'  => Tools_Mail_SystemMailWatchdog::TRIGGER_FORMSENT,
                    'data'     => $formParams
                ));
                $mailWatchdog->notify($form);
                $mailsSent = $sysMailWatchdog->notify($form);
                if($mailsSent) {
                    $form->notifyObservers();
                    $this->_helper->response->success($form->getMessageSuccess());
                }
                $this->_helper->response->fail($form->getMessageError());
			}
        }
    }

    public function refreshcaptchaAction() {
        if($this->getRequest()->isPost()) {
            $this->_helper->json(Tools_System_Tools::generateCaptcha());
        }
    }

	private function _validateCaptcha($captchaInput, $captchaId) {
        $captcha     = new Zend_Session_Namespace('Zend_Form_Captcha_' . $captchaId);
		$captchaData = $captcha->getIterator();
		return ($captchaData['word'] == $captchaInput);
	}
    
    private function _createTrackingPage($formName, $templateName){
        $trackingPageName   = 'form-'.$formName.'-thank-you';
        $trackingPageUrl   = $this->_helper->page->filterUrl($trackingPageName);
        $pageMapper         = Application_Model_Mappers_PageMapper::getInstance();
        $pageModel          = new Application_Model_Models_Page();
        $trackingPageExist = $pageMapper->findByUrl($trackingPageUrl);
        if(empty($trackingPageExist)){
            $pageModel->setParentId(-1);
            $pageModel->setDraft(0);
            $pageModel->setTemplateId($templateName);
            $pageModel->setH1($trackingPageName);
            $pageModel->setHeaderTitle($trackingPageName);
            $pageModel->setMetaDescription($trackingPageName);
            $pageModel->setNavName($trackingPageName);
            $pageModel->setUrl($trackingPageUrl);
            $pageModel->setSystem(0);
            $pageMapper->save($pageModel);
        }
        return $trackingPageUrl;
    }
    
    private function _addConversionCode(){
        $pageMapper    = Application_Model_Mappers_PageMapper::getInstance();
        $seoDataMapper = Application_Model_Mappers_SeodataMapper::getInstance();
        $seoDataModel  = new Application_Model_Models_Seodata();
        $seoData = $seoDataMapper->fetchAll();
        if(empty($seoData)){
            $seoDataModel->setSeoTop('{$form:conversioncode}');
            $seoDataMapper->save($seoDataModel);
        }else{
            $seoTopData = $seoData[0]->getSeoTop();
            $id         = $seoData[0]->getId();
            if(!preg_match('~\{\$form\:conversioncode\}~',$seoTopData)){
                $seoDataModel->setId($id);
                $seoDataModel->setSeoTop($seoTopData.' {$form:conversioncode}');
                $seoDataMapper->save($seoDataModel);
            }
        }
    }

}