<?php
/**
 * FormController
 *
 * @author Seotoaser Dev Team
 */
class Backend_FormController extends Zend_Controller_Action {

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
		if($this->getRequest()->isPost()) {
			if($formForm->isValid($this->getRequest()->getParams())) {

				$form = new Application_Model_Models_Form($this->getRequest()->getParams());
                $contactEmail = $form->getContactEmail();
                $validEmail = $this->validateEmail($contactEmail);
                if(isset($validEmail['error'])){
                    $this->_helper->response->fail(Tools_Content_Tools::proccessFormMessagesIntoHtml(array('contactEmail'=>$validEmail['error']), get_class($formForm)));
                }
                Application_Model_Mappers_FormMapper::getInstance()->save($form);
                $this->_helper->cache->clean('', '', array(Widgets_Form_Form::WFORM_CACHE_TAG));
				$this->_helper->response->success($this->_helper->language->translate('Form saved'));
			}
			else {
				$this->_helper->response->fail(Tools_Content_Tools::proccessFormMessagesIntoHtml($formForm->getMessages(), get_class($formForm)));
			}
		}
		$formName      = filter_var($this->getRequest()->getParam('name'), FILTER_SANITIZE_STRING);
		$form          = Application_Model_Mappers_FormMapper::getInstance()->findByName($formName);
		$mailTemplates = Tools_Mail_Tools::getMailTemplatesHash();
		$formForm->getElement('name')->setValue($formName);
		$formForm->getElement('replyMailTemplate')->setMultioptions(array_merge(array(0 => 'select template'), $mailTemplates));
		if($form !== null) {
			$formForm->populate($form->toArray());
		}
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
			if(!empty ($formParams)) {

				//validating captcha
                if(isset($formParams['captcha'])) {
					if(!$this->_validateCaptcha(strtolower($formParams['captcha']), $formParams['captchaId'])) {
                        $this->_helper->response->fail($this->_helper->language->translate('Captcha is not valid.'));
					}
				}

                // get the form details
				$form   = Application_Model_Mappers_FormMapper::getInstance()->findByName($formParams['formName']);
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


}