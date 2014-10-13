<?php
class MagicSpaces_Notadmin_Notadmin extends Tools_MagicSpaces_Abstract {

	protected function _run() {
		return (!Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_PLUGINS)) ? $this->_spaceContent : '';
	}

}
