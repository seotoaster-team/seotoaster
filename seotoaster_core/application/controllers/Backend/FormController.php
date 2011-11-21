<?php
/**
 * FormController
 *
 * @author Seotoaser Dev Team
 */
class Backend_FormController extends Zend_Controller_Action {

    public function init() {
		parent::init();
		if(!Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_CONTENT) && !Tools_Security_Acl::isActionAllowed('Form', $this->getRequest()->getParam('action'))) {
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
				Application_Model_Mappers_FormMapper::getInstance()->save($form);
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
		$formForm->getElement('replyMailTemplate')->setMultioptions($mailTemplates);
		if($form !== null) {
			$formForm->populate($form->toArray());
		}
		$this->view->formForm = $formForm;
	}

    public function deleteAction(){
       $id         = $this->getRequest()->getParam('id');
       $formMapper = Application_Model_Mappers_FormMapper::getInstance();
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

				if(isset($formParams['captcha'])) {
					if(!$this->_validateCaptcha($formParams['captcha'], $formParams['captchaId'])) {
						$this->_helper->response->fail($this->_helper->language->translate('Captcha is not valid.'));
					}
				}

				$form   = Application_Model_Mappers_FormMapper::getInstance()->findByName($formParams['formName']);
				$mailer = new Tools_Mail_Mailer();

				// To site owner
				if(isset($formParams['email'])) {

					$validator = new Zend_Validate_EmailAddress();
					if(!$validator->isValid($formParams['email'])) {
						$this->_helper->response->fail($this->_helper->language->translate('Email address is not valid'));
					}

					$mailer->setMailTo($form->getContactEmail());
					$mailer->setMailFrom($formParams['email']);
					if(isset($formParams['name'])) {
						$mailer->setMailFromLabel($formParams['name']);
					}
					$mailer->setSubject($this->_helper->language->translate('New mesage was posted on the website'));

					$this->view->badParams = array('controller', 'action', 'module', 'formName', 'captcha', 'captchaId');
					$this->view->params    = $formParams;
					$mailer->setBody($this->view->render('backend/form/adminmail.phtml'));

					if($mailer->send()) {
						// sending reply
						$mailer = new Tools_Mail_Mailer();
						$mailer->setMailTo($formParams['email']);
						$mailer->setMailFrom($form->getReplyFrom());
						$mailer->setSubject($form->getReplySubject());
						$mailer->setMailTemplateName($form->getReplyMailTemplate(), true);
						$mailer->send();

						$this->_helper->response->success($form->getMessageSuccess());
						exit;
					}
					$this->_helper->response->fail($form->getMessageError());
					exit;
				}
			}
        }
    }

	private function _validateCaptcha($captchaInput, $captchaId) {
		$captcha     = new Zend_Session_Namespace('Zend_Form_Captcha_' . $captchaId);
		$captchaData = $captcha->getIterator();
		if(isset($captchaData['word'])) {
			return ($captchaInput == $captchaData['word']);
		}
		return false;
	}


}