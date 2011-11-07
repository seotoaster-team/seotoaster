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
			'deleteform'  => 'json',
			'receiveform' => 'json'
		))->initContext('json');
    }

    public function manageformAction() {
		$formForm = new Application_Form_Form();
        if($this->getRequest()->isPost()) {
            if ($formForm->isValid($this->getRequest()->getParams())){
               $form  = new Application_Model_Models_Form($this->getRequest()->getParams());
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
		if($form !== null){
			$formForm->populate($form->toArray());
		}
        $this->view->formForm = $formForm;
    }

    public function deleteformAction(){
       $excistForm = $this->getRequest()->getParam('excistForm');
       $formMapper = Application_Model_Mappers_FormMapper::getInstance();
       $formMapper->deleteForm($formMapper->findFormByName($excistForm));
    }

    public function receiveformAction(){
        if($this->getRequest()->isPost()) {
            $formParams = $this->getRequest()->getParams();
			if(!empty ($formParams)) {
				$form   = Application_Model_Mappers_FormMapper::getInstance()->findByName($formParams['formName']);
				$mailer = new Tools_Mail_Mailer();
				// To site owner
				if(isset($formParams['email'])) {
					$mailer->setMailTo($form->getContactMail());
					$mailer->setMailFrom($formParams['email']);
					$mailer->setSubject($this->_helper->language->translate('New message was posted'));
					$mailer->setBody($this->view->render('adminmail.phtml'));
					$mailer->prepare();
					$mailer->send();
				}
			}
        }
    }

}