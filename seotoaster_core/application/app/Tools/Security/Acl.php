<?php

/**
 * Description of AclTools
 *
 * @author iamne
 */
class Tools_Security_Acl {

	// roles
	const ROLE_GUEST      = 'guest';
	const ROLE_MEMBER     = 'member';
	const ROLE_USER       = 'user';
	const ROLE_ADMIN      = 'admin';
	const ROLE_SUPERADMIN = 'superadmin';
	const ROLE_SYSTEM     = 'system';

	// resources
	const RESOURCE_PAGE_PUBLIC    = 'publicpage';
	const RESOURCE_PAGE_PROTECTED = 'protectedpage';
	const RESOURCE_CONTENT        = 'content';
	const RESOURCE_WIDGETS        = 'widgets';
	const RESOURCE_CODE           = 'code';
	const RESOURCE_ADMINPANEL     = 'adminpanel';
	const RESOURCE_PAGES          = 'pages';
	const RESOURCE_MEDIA          = 'media';
	const RESOURCE_SEO            = 'seo';
	const RESOURCE_LAYOUT         = 'layout';
	const RESOURCE_CONFIG         = 'config';
	const RESOURCE_USERS          = 'users';
	const RESOURCE_PLUGINS        = 'plugins';
	const RESOURCE_CACHE_PAGE     = 'cachepage';
	const RESOURCE_THEMES         = 'themes';

	private static $_allowedActions = array(
		'Page' => array(
			'publishpages'
		),
		'Form' => array(
			'receiveform'
		),
		'Plugin' => array(
			'fireaction'
		)
	);

	/**
     * Check if user allowed to access to resource
	 *
     * @static
     * @param string $resource Name of resource
     * @param string $role User role. If not given - current logged user role will be used
     * @return bool
     */
    public static function isAllowed($resource, $role = '') {
		if(!$role) {
			$sessionHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('Session');
			$role          = $sessionHelper->getCurrentUser()->getRoleId();
		}
		$acl = Zend_Registry::get('acl');
		return $acl->has($resource) ? $acl->isAllowed($role, $resource) : true ;
	}

	/**
	 * Get current controller and action and check if this action exists in
	 *
	 * Allowed actions list. "Allowed action list" shold be specified in the controller
	 * Via public static property: public static $_allowedActions = array('receiveform');
	 *
	 * @return bool
	 */
	public static function isActionAllowed() {
		if(self::isAllowed(self::RESOURCE_ADMINPANEL)) {
			return true;
		}
		$controller          = Zend_Controller_Front::getInstance()->getRequest()->getParam('controller');
		$controllerClassName = implode('_', array_map(function($part) {
			return ucfirst($part);
		}, explode('_', $controller))) . 'Controller';
		return (in_array(Zend_Controller_Front::getInstance()->getRequest()->getParam('action'), $controllerClassName::$_allowedActions));
	}

}

