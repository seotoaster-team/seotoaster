<?php

/**
 * NewsController
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class NewsController extends Zend_Controller_Action {

    public function indexAction() {
		//Zend_Debug::dump($this->getRequest()->getParams()); die();
		if($this->getRequest()->getParam('page') == 'index.html') {
			$this->_forward('list');
		}
		else {
			$this->_forward('index', 'index');
		}
    }


    public function listAction() {
		Zend_Debug::dump('list mo fo'); die();
	}

    public function viewAction() {

    }
}

