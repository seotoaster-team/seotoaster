<?php

/**
 * NewsController
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class NewsController extends Zend_Controller_Action {

    public function indexAction() {
        $this->_forward('list');
    }


    public function listAction() {

	}

    public function viewAction() {
		
    }
}

