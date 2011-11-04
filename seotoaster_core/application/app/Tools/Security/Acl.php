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
		)
	);

	public static function isAllowed($resourse, $role = '') {
		if(!$role) {
			$sessionHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('Session');
			$role          = $sessionHelper->getCurrentUser()->getRoleId();
		}
		$acl = Zend_Registry::get('acl');
		return $acl->isAllowed($role, $resourse);
	}

	public static function isActionAllowed($controller, $action) {
		$actions = self::$_allowedActions[$controller];
		return (in_array($action, $actions));
	}

}

