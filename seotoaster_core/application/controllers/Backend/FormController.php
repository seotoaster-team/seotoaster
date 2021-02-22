<?php
/**
 * FormController
 *
 * @author Seotoaser Dev Team
 */
class Backend_FormController extends Zend_Controller_Action {

    const FORM_THANKYOU_PAGE = 'option_formthankyoupage';
    const ATTACHMENTS_FILE_TYPES = 'xml,csv,doc,zip,jpg,png,bmp,gif,xls,pdf,docx,txt,xlsx,jpeg';

	public static $_allowedActions = array(
		'receiveform',
        'refreshcaptcha'
	);

    public function init() {
		parent::init();
		if(!Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_CONTENT) && !Tools_Security_Acl::isActionAllowed(Tools_Security_Acl::RESOURCE_CONTENT)) {
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
            $formForm = Tools_System_Tools::addTokenValidatorZendForm($formForm, Tools_System_Tools::ACTION_PREFIX_FORMS);

            $replyEmail = $this->getRequest()->getParam('replyEmail');
            if(!empty($replyEmail)) {
                $formForm->getElement('replySubject')->setRequired(false);
                $formForm->getElement('replyFrom')->setRequired(false);
            }

            if($formForm->isValid($this->getRequest()->getParams())) {
                $formPageConversionModel = new Application_Model_Models_FormPageConversion();
                $formData = $this->getRequest()->getParams();
				$form = new Application_Model_Models_Form($this->getRequest()->getParams());
                if(isset($formData['thankyouTemplate']) && $formData['thankyouTemplate'] != 'select'){
                    $trackingPageUrl = $this->_createTrackingPage($formData['name'], $formData['thankyouTemplate']);
                }
                $this->_addConversionCode();
                $formPageConversionModel->setFormName($formData['name']);
                $formPageConversionModel->setPageId($formData['pageId']);
                $formPageConversionModel->setConversionCode($formData['trackingCode']);
                $formPageConversionMapper->save($formPageConversionModel);
                Application_Model_Mappers_FormMapper::getInstance()->save($form);
				$this->_helper->response->success($this->_helper->language->translate('Form saved'));
			}
			else {
				$this->_helper->response->fail(Tools_Content_Tools::proccessFormMessagesIntoHtml($formForm->getMessages(), get_class($formForm)));
			}
		}
		$formName           = filter_var($this->getRequest()->getParam('name'), FILTER_SANITIZE_STRING);

        $secureToken = Tools_System_Tools::initZendFormCsrfToken($formForm, Tools_System_Tools::ACTION_PREFIX_FORMS);

        $this->view->secureToken = $secureToken;

        $pageId             = $this->getRequest()->getParam('pageId');
        $trackingPageName   = 'form-'.$formName.'-thank-you';
        $trackingPageUrl    = $this->_helper->page->filterUrl($trackingPageName);
        $trackingPageExist  = $pageMapper->findByUrl($trackingPageUrl);
        if(!empty($trackingPageExist)){
            $this->view->trackingPageUrl = $trackingPageUrl;
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
		$formForm->getElement('adminMailTemplate')->setMultioptions(array_merge(array(0 => 'select template'), $mailTemplates));

        $replyEmail = 0;
		if($form !== null) {
		    if($form->getReplyEmail()) {
                $replyEmail = 1;
            }

			$formForm->populate($form->toArray());
		}

        $this->view->replyEmail = $replyEmail;
        $this->view->regularTemplates = $regularPageTemplates;
        $this->view->pageId = $pageId;
		$this->view->formForm = $formForm;
	    $this->view->helpSection = 'editform';
	}

    public function validateEmail($emails){
        $emailValidation = new Tools_System_CustomEmailValidator();
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


    public function deleteAction()
    {
        if ($this->_request->isDelete()) {
            $id = filter_var($this->getRequest()->getParam('id'), FILTER_SANITIZE_NUMBER_INT);
            $formMapper = Application_Model_Mappers_FormMapper::getInstance();
            return $formMapper->delete($formMapper->find($id));
        }
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
            $xmlHttpRequest = $this->_request->isXmlHttpRequest();
            $formParams    = $this->getRequest()->getParams();
            $sessionHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('Session');
			if(!empty ($formParams)) {
                $websiteConfig = Zend_Controller_Action_HelperBroker::getExistingHelper('config')->getConfig();
                $formMapper = Application_Model_Mappers_FormMapper::getInstance();
                // get the form details
				$form   = $formMapper->findByName($formParams['formName']);
                $useCaptcha = $form->getCaptcha();

                //hidden input validation
                $formName = $form->getName();
                $formId   = $form->getId();
                if(!isset($formParams[md5($formName.$formId)]) || $formParams[md5($formName.$formId)] != ''){
                    if($xmlHttpRequest){
                        $this->_helper->response->success($form->getMessageSuccess());
                    }
                    $this->_redirect($formParams['formUrl']);
                }
                unset($formParams[md5($formName.$formId)]);



                //validating recaptcha
                if($useCaptcha == 1 && !isset($formParams['g-recaptcha-response'])){
                    if(!empty($websiteConfig) && !empty($websiteConfig[Tools_System_Tools::RECAPTCHA_PUBLIC_KEY])
                            && !empty($websiteConfig[Tools_System_Tools::RECAPTCHA_PRIVATE_KEY])
                            && isset($formParams['recaptcha_challenge_field']) || isset($formParams['captcha'])){
                        
                        if(isset($formParams['recaptcha_challenge_field']) && isset($formParams['recaptcha_response_field'])) {
                            if($formParams['recaptcha_response_field'] == ''){
                                if($xmlHttpRequest){
                                    $this->_helper->response->fail($this->_helper->language->translate('You\'ve entered an incorrect security text. Please try again.'));
                                }
                                $sessionHelper->toasterFormError = $this->_helper->language->translate('You\'ve entered an incorrect security text. Please try again.');
                                $this->_redirect($formParams['formUrl']);
                            }
                            $recaptcha = new Zend_Service_ReCaptcha($websiteConfig[Tools_System_Tools::RECAPTCHA_PUBLIC_KEY], $websiteConfig[Tools_System_Tools::RECAPTCHA_PRIVATE_KEY]);
                            $result = $recaptcha->verify($formParams['recaptcha_challenge_field'], $formParams['recaptcha_response_field']);
                            if(!$result->isValid()){
                                if($xmlHttpRequest){
                                    $this->_helper->response->fail($this->_helper->language->translate('You\'ve entered an incorrect security text. Please try again.'));
                                }
                                $sessionHelper->toasterFormError = $this->_helper->language->translate('You\'ve entered an incorrect security text. Please try again.');
                                $this->_redirect($formParams['formUrl']);
                            }
                            unset($formParams['recaptcha_challenge_field']);
                            unset($formParams['recaptcha_response_field']);
                        }else{
                            //validating captcha
                            if(!$this->_validateCaptcha(strtolower($formParams['captcha']), $formParams['captchaId'])) {
                                if($xmlHttpRequest){
                                    $this->_helper->response->fail($this->_helper->language->translate('You\'ve entered an incorrect security text. Please try again.'));
                                }
                                $sessionHelper->toasterFormError = $this->_helper->language->translate('You\'ve entered an incorrect security text. Please try again.');
                                $this->_redirect($formParams['formUrl']);
                            }
                        }
                    }else{
                        if($xmlHttpRequest){
                            $this->_helper->response->fail($this->_helper->language->translate('You\'ve entered an incorrect security text. Please try again.'));
                        }
                        $sessionHelper->toasterFormError = $this->_helper->language->translate('You\'ve entered an incorrect security text. Please try again.');
                        $this->_redirect($formParams['formUrl']);
                    }

                } elseif ($useCaptcha == 1 && isset($formParams['g-recaptcha-response'])) {

                    $googleRecaptcha = new Tools_System_GoogleRecaptcha();
                    if(!$googleRecaptcha->isValid($formParams['g-recaptcha-response'])){
                        if (!$googleRecaptcha->isValid($formParams['g-recaptcha-response'])) {
                            if ($xmlHttpRequest) {
                                $this->_helper->response->fail($this->_helper->language->translate('Please check the anti-spam \'I am not a robot\' checkbox!'));
                            }
                            $sessionHelper->toasterFormError = $this->_helper->language->translate('Please check the anti-spam \'I am not a robot\' checkbox!');
                            $this->_redirect($formParams['formUrl']);
                        }
                    }

                }

                //Check if email is valid
                if (isset($formParams['email'])) {
                    $emailValidation = new Tools_System_CustomEmailValidator();
                    $validEmail = $emailValidation->isValid($formParams['email']);
                    if(!$validEmail){
                        if($xmlHttpRequest){
                            $this->_helper->response->fail($this->_helper->language->translate('Please enter a valid email address'));
                        }
                        $sessionHelper->toasterFormError = $this->_helper->language->translate('Please enter a valid email address');
                        $this->redirect($formParams['formUrl']);
                    }
                }

                if (Tools_System_FormBlacklist::isBlacklisted($formParams['email'], $formParams)) {
                    if($xmlHttpRequest){
                        $this->_helper->response->success($form->getMessageSuccess());
                    }

                    if(isset($formParams['conversionPageUrl'])) {
                        $conversionPageUrl = $formParams['conversionPageUrl'];
                        $this->redirect($conversionPageUrl);
                    }

                    $sessionHelper->toasterFormSuccess = $form->getMessageSuccess();
                    $this->redirect($formParams['formUrl']);
                }

                $sessionHelper->formName   = $formParams['formName'];
                $sessionHelper->formPageId = $formParams['formPageId'];
				unset($formParams['formPageId']);
                unset($formParams['submit']);
                if(isset($formParams['conversionPageUrl'])){
                    $conversionPageUrl = $formParams['conversionPageUrl'];
                    unset($formParams['conversionPageUrl']);
                }

                $attachment = array();
                $removeFiles = array();
                if(!$xmlHttpRequest){
                    //Adding attachments to email
                    $websitePathTemp = $this->_helper->website->getPath().$this->_helper->website->getTmp();
                    $uploader = new Zend_File_Transfer_Adapter_Http();
                    $uploader->setDestination($websitePathTemp);
                    $uploader->addValidator('Extension', false, self::ATTACHMENTS_FILE_TYPES);
                    //Adding Size limitation
                    $uploader->addValidator('Size', false, $formParams['uploadLimitSize']*1024*1024);
                    //Adding mime types validation
                    $uploader->addValidator('MimeType', true, array('application/pdf','application/xml', 'application/zip', 'text/csv', 'text/plain', 'image/png','image/jpeg',
                                                                    'image/gif', 'image/bmp', 'application/msword', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'));
                    $files = $uploader->getFileInfo();
                    foreach($files as $file => $fileInfo) {
                        if($fileInfo['name'] != ''){
                            if($uploader->isValid($file)) {
                                $uploader->receive($file);
                                $at              = new Zend_Mime_Part(file_get_contents($uploader->getFileName($file)));
                                $at->type        = $uploader->getMimeType($file);
                                $at->disposition = Zend_Mime::DISPOSITION_ATTACHMENT;
                                $at->encoding    = Zend_Mime::ENCODING_BASE64;
                                $at->filename    = $fileInfo['name'];
                                $attachment[]    = $at;
                                unset($at);
                                $removeFiles[] = $this->_helper->website->getPath().$this->_helper->website->getTmp().$fileInfo['name'];
                            }else{
                                $validationErrors = $uploader->getErrors();
                                $errorMessage = '';
                                foreach($validationErrors as $errorType){
                                    if($errorType == 'fileMimeTypeFalse'){
                                        $errorMessage .= 'Invalid file format type. ';
                                    }
                                    if($errorType == 'fileSizeTooBig'){
                                        $errorMessage .= $this->_helper->language->translate('Maximum size upload').' '.$formParams['uploadLimitSize'].'mb.';
                                    }
                                    if($errorType == 'fileExtensionFalse'){
                                        $errorMessage .= 'File extension not valid. ';
                                    }
                                }
                                $sessionHelper->toasterFormError = $this->_helper->language->translate($errorMessage);
                                $this->_redirect($formParams['formUrl']);
                            }
                        }
                    }

                }
                unset($formParams['uploadLimitSize']);
                unset($formParams['g-recaptcha-response']);
               	// sending mails
                $sysMailWatchdog = new Tools_Mail_SystemMailWatchdog(array(
                    'trigger'    => Tools_Mail_SystemMailWatchdog::TRIGGER_FORMSENT,
                    'data'       => $formParams,
                    'attachment' => $attachment
                ));
                $mailWatchdog = new Tools_Mail_Watchdog(array(
                    'trigger'  => Tools_Mail_SystemMailWatchdog::TRIGGER_FORMSENT,
                    'data'     => $formParams,
                    'attachment' => $attachment
                ));

                $form->setAdminFrom($this->_parseData($form->getAdminFrom()));
                $form->setAdminSubject(html_entity_decode($this->_parseData($form->getAdminSubject()), null, 'UTF-8'));
                $form->setAdminFromName(html_entity_decode($this->_parseData($form->getAdminFromName()), null, 'UTF-8'));
                $form->setAdminText($this->_parseData($form->getAdminText()));
                $form->setReplyText($this->_parseData($form->getReplyText()));
                $form->setContactEmail($this->_parseData($form->getContactEmail()));
                $form->setReplyFrom($this->_parseData($form->getReplyFrom()));
                $form->setReplyFromName(html_entity_decode($this->_parseData($form->getReplyFromName()), null, 'UTF-8'));
                $form->setReplySubject(html_entity_decode($this->_parseData($form->getReplySubject()), null, 'UTF-8'));
                $form->setMobile($this->_parseData($form->getMobile()));

                $mailWatchdog->notify($form);
                $mailsSent = $sysMailWatchdog->notify($form);
                if($mailsSent) {
                    $form->notifyObservers();
                    $this->_removeAttachedFiles($removeFiles);
                    if($xmlHttpRequest){
                        $this->_helper->response->success($form->getMessageSuccess());
                    }
                    //redirect to conversion page
                    if($conversionPageUrl){
                        $this->_redirect($conversionPageUrl);
                    }
                    $sessionHelper->toasterFormSuccess = $form->getMessageSuccess();
                    $this->_redirect($formParams['formUrl']);
                }
                $this->_removeAttachedFiles($removeFiles);
                if($xmlHttpRequest){
                    $this->_helper->response->fail($form->getMessageError());
                }
                $sessionHelper->toasterFormError = $form->getMessageError();
                $this->_redirect($formParams['formUrl']);
			}
        }
    }

    /**
     * Parse widgets for template
     *
     * @param string $data
     * @return null
     * @throws Zend_Exception
     */
    private function _parseData($data)
    {
        $themeData = Zend_Registry::get('theme');
        $extConfig = Zend_Registry::get('extConfig');
        $parserOptions = array(
            'websiteUrl'   => $this->_helper->website->getUrl(),
            'websitePath'  => $this->_helper->website->getPath(),
            'currentTheme' => $extConfig['currentTheme'],
            'themePath'    => Tools_Filesystem_Tools::cleanWinPath($themeData['path']),
        );
        $parser = new Tools_Content_Parser($data, array(), $parserOptions);

        return $parser->parseSimple();
    }

    private function _removeAttachedFiles(array $removeFiles)
    {
        if(!empty($removeFiles)) {
            foreach($removeFiles as $file){
                Tools_Filesystem_Tools::deleteFile($file);
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
        $trackingName = 'Form '.$formName.' Thank you';
        $trackingPageUrl   = $this->_helper->page->filterUrl($trackingPageName);
        $pageMapper         = Application_Model_Mappers_PageMapper::getInstance();
        $pageModel          = new Application_Model_Models_Page();
        $trackingPageExist = $pageMapper->findByUrl($trackingPageUrl);
        if(empty($trackingPageExist)){
            $pageModel->setParentId(-1);
            $pageModel->setDraft(0);
            $pageModel->setTemplateId($templateName);
            $pageModel->setH1($trackingName);
            $pageModel->setHeaderTitle($trackingName);
            $pageModel->setMetaDescription($trackingName);
            $pageModel->setNavName($trackingName);
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
            $seoTopData    = $seoData[0]->getSeoTop();
            $seoHeadData   = $seoData[0]->getSeoHead();
            $seoBottomData = $seoData[0]->getSeoBottom();
            $id          = $seoData[0]->getId();
            if(!preg_match('~\{\$form\:conversioncode\}~',$seoTopData)){
                $seoDataModel->setId($id);
                $seoDataModel->setSeoTop($seoTopData.' {$form:conversioncode}');
                $seoDataModel->setSeoHead($seoHeadData);
                $seoDataModel->setSeoBottom($seoBottomData);
                $seoDataMapper->save($seoDataModel);
            }
        }
    }

}
