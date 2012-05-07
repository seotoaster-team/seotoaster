<?php
/**
 * ToasterAdminpanelItem.php
 * @author Pavel Kovalyov <pavlo.kovalyov@gmail.com>
 */
class Zend_View_Helper_ToasterAdminpanelItem extends Zend_View_Helper_Abstract {

	public function toasterAdminpanelItem($menuItem){
		if (is_string($menuItem)) {
			return '<li>'.$menuItem.'</li>';
		} elseif (is_array($menuItem) && !empty($menuItem)) {
			return '<li>'. $this->view->toasterLink(
				'plugin', $menuItem['name'],
				isset($menuItem['title'])?$menuItem['title']:$menuItem['run'],
				array('run' => $menuItem['run']),
				false,
				array('width' => $menuItem['width'], 'height' => $menuItem['height'])
			) .'</li>';
		}
	}
}
