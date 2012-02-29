<?php

class MagicSpaces_Memberonly_Memberonly extends Tools_MagicSpaces_Abstract {

	protected function _run() {
		return (Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_PAGE_PROTECTED)) ? $this->_spaceContent : '';
	}
}
