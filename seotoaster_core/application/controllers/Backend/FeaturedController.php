<?php

/**
 * FeaturedController
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Backend_FeaturedController extends Zend_Controller_Action{

	public function init() {
		parent::init();
		if(!Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_PAGES)) {
			$this->_redirect($this->_helper->website->getUrl(), array('exit' => true));
		}
		$this->view->websiteUrl = $this->_helper->website->getUrl();
		$this->_helper->AjaxContext()->addActionContexts(array(
			'loadfalist'    => 'json',
			'addpagetofa'   => 'json',
			'rempagefromfa' => 'json',
			'delete'        => 'json'
		))->initContext('json');
	}

	public function featuredAction() {
		$featuredForm = new Application_Form_Featured();
		if($this->getRequest()->isPost()) {
            $featuredForm = Tools_System_Tools::addTokenValidatorZendForm($featuredForm, Tools_System_Tools::ACTION_PREFIX_PAGES);
            if($featuredForm->isValid($this->getRequest()->getParams())) {
				$featuredArea = new Application_Model_Models_Featuredarea($featuredForm->getValues());
				Application_Model_Mappers_FeaturedareaMapper::getInstance()->save($featuredArea);
				$this->_helper->response->success('Added');
				exit;
			}
			else {
				$this->_helper->response->fail(Tools_Content_Tools::proccessFormMessagesIntoHtml($featuredForm->getMessages(), get_class($featuredForm)));
			}
		}
		$pageId                    = $this->getRequest()->getParam('pid');
		$this->view->pageId        = $pageId;
		$this->view->faForm        = $featuredForm;
        $secureToken = Tools_System_Tools::initZendFormCsrfToken($featuredForm, Tools_System_Tools::ACTION_PREFIX_PAGES);
        $this->view->secureToken = $secureToken;
		if(isset ($this->_helper->session->faPull)) {
			unset($this->_helper->session->faPull);
		}
	}

	public function loadfalistAction() {
		$render        = $this->getRequest()->getParam('render', true);
		$namesOnly     = $this->getRequest()->getParam('namesonly', false);
		$featuredAreas = Application_Model_Mappers_FeaturedareaMapper::getInstance()->fetchFaList();

		if($namesOnly) {
			$names = array();
			foreach ($featuredAreas as $area) {
				 $names[] = array(
					 'name' => $area->getName(),
					 'id'   => $area->getId()
				 );
			}
			$this->view->responseData = $names;
		}
        asort($featuredAreas);
		$this->view->faeaturedAreas = $featuredAreas;
		$pageId                     = $this->getRequest()->getParam('pid');
		if($pageId) {
			$ids = array();
			$currentFareas = Application_Model_Mappers_FeaturedareaMapper::getInstance()->findAreasByPageId($pageId);
			if(!empty ($currentFareas)) {
				foreach ($currentFareas as $currFa) {
					$ids[] = $currFa->getId();
				}
			}
		}
		$this->view->currentFareasIds  = (!empty($ids)) ? $ids : array();

		if($render) {
			$this->view->faList = $this->view->render('backend/featured/falist.phtml');
		}
	}

	public function addpagetofaAction() {
		if($this->getRequest()->isPost()) {
            $tokenToValidate = $this->getRequest()->getParam(Tools_System_Tools::CSRF_SECURE_TOKEN, false);
            $valid = Tools_System_Tools::validateToken($tokenToValidate, Tools_System_Tools::ACTION_PREFIX_PAGES);
            if (!$valid) {
                exit;
            }
            $page     = Application_Model_Mappers_PageMapper::getInstance()->find($this->getRequest()->getParam('pid'));
			$fa       = Application_Model_Mappers_FeaturedareaMapper::getInstance()->find($this->getRequest()->getParam('faid'), false);
			$fa->registerObserver(new Tools_Featured_GarbageCollector(array(
				'action' => Tools_System_GarbageCollector::CLEAN_ONUPDATE
			)));
			if(!$fa instanceof Application_Model_Models_Featuredarea) {

			}
			if(!$page instanceof Application_Model_Models_Page) {
				//page is no created yet, but we want to add it to fa
				$faPull                         = isset($this->_helper->session->faPull) ? $this->_helper->session->faPull : array();
				$faPull[]                       = $fa->getId();
				$this->_helper->session->faPull = $faPull;
				$this->_helper->response->success($this->_helper->language->translate('Page added to featured area'));
				//return;
			}
			$fa->addPage($page);
			Application_Model_Mappers_FeaturedareaMapper::getInstance()->save($fa);
			$fa->notifyObservers();
			$this->_helper->response->success($this->_helper->language->translate('Page added to featured area'));
		}
	}

	public function rempagefromfaAction() {
		if($this->getRequest()->isPost()) {
            $tokenToValidate = $this->getRequest()->getParam(Tools_System_Tools::CSRF_SECURE_TOKEN, false);
            $valid = Tools_System_Tools::validateToken($tokenToValidate, Tools_System_Tools::ACTION_PREFIX_PAGES);
            if (!$valid) {
                exit;
            }
            $page     = Application_Model_Mappers_PageMapper::getInstance()->find($this->getRequest()->getParam('pid'));
			$fa       = Application_Model_Mappers_FeaturedareaMapper::getInstance()->find($this->getRequest()->getParam('faid'), false);
			$fa->registerObserver(new Tools_Featured_GarbageCollector(array(
				'action' => Tools_System_GarbageCollector::CLEAN_ONUPDATE
			)));
			if(!$page instanceof Application_Model_Models_Page) {
				//page is no created yet, but we want to add it to fa
				$faPull = $this->_helper->session->faPull;
				if(is_array($faPull) && !empty ($faPull)) {
					if(in_array($fa->getId(), $faPull)) {
						unset($faPull[array_search($fa->getId(), $faPull)]);
						$this->_helper->session->faPull = $faPull;
					}
				}
				$this->_helper->response->success($this->_helper->language->translate('Page removed from featured area'));
			}
			$fa->deletePage($page);
			Application_Model_Mappers_FeaturedareaMapper::getInstance()->save($fa);
			$fa->notifyObservers();
			$this->_helper->response->success($this->_helper->language->translate('Page removed from featured area'));
		}
	}

	public function orderAction() {
		$faId = intval($this->getRequest()->getParam('id'));
		if(!$faId) {
			throw new Exceptions_SeotoasterException('Wrong featured area id');
		}
		$featuredArea = Application_Model_Mappers_FeaturedareaMapper::getInstance()->find($faId);
		if(!$featuredArea instanceof Application_Model_Models_Featuredarea) {
			throw new Exceptions_SeotoasterException('Cannot load featured area');
		}
		if($this->getRequest()->isPost()) {
            $tokenToValidate = $this->getRequest()->getParam(Tools_System_Tools::CSRF_SECURE_TOKEN, false);
            $valid = Tools_System_Tools::validateToken($tokenToValidate, Tools_System_Tools::ACTION_PREFIX_FAREA);
            if (!$valid) {
                exit;
            }
            $featuredArea->registerObserver(new Tools_Featured_GarbageCollector(array(
				'action' => Tools_System_GarbageCollector::CLEAN_ONUPDATE
			)));
			$ordered = $this->getRequest()->getParam('ordered');
			Application_Model_Mappers_FeaturedareaMapper::getInstance()->saveFaOrder($ordered, $faId);
			$featuredArea->notifyObservers();
		}

        $secureToken = Tools_System_Tools::initSecureToken(Tools_System_Tools::ACTION_PREFIX_FAREA);
        $this->view->secureToken = $secureToken;
		$this->view->faPages = $featuredArea->getPages();
        $this->view->faName = $featuredArea->getName();
        $this->view->faId = $faId;
	}

    public function deleteAction()
    {
        if ($this->getRequest()->isDelete()) {
            $faId = explode(',', $this->getRequest()->getParam('id'));
            if (is_array($faId) && !empty ($faId)) {
                foreach ($faId as $id) {
                    $this->_delete($id);
                }
            }
        }
    }

	private function _delete($id) {
		$faMapper     = Application_Model_Mappers_FeaturedareaMapper::getInstance();
		$featuredArea = $faMapper->find($id);
		if($featuredArea instanceof Application_Model_Models_Featuredarea) {

			return $faMapper->delete($featuredArea);
		}
	}

}

