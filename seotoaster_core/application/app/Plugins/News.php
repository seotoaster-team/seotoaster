<?php

/**
 * StartupoHook
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Plugins_News extends Zend_Controller_Plugin_Abstract {

	public function routeStartup(Zend_Controller_Request_Abstract $request) {
		$configHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('config');
		$configHelper->init();
		$newsFolder = $configHelper->getConfig('newsFolder');
		unset ($configHelper);
		if($newsFolder) {
			$router = Zend_Controller_Front::getInstance()->getRouter();
			$router->addRoute('newsRoute',
				new Zend_Controller_Router_Route($newsFolder . '/:page', array(
					'controller' => 'news',
					'action'     => 'index',
					'context'    => 'news'
				))
			);
		}
	}
}

