<?php

/**
 * UrlRegex
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Validators_UrlRegex extends Zend_Validate_Abstract {

	const URL = 'url';

	protected $_messageTemplates = array(
		self::URL => "'%value%' is not valid url"
	);

	public function isValid($value) {
		$validator = new Zend_Validate_Regex(array(
			//'pattern' => '/^(([\w]+:)?\/\/)?(([\d\w]|%[a-fA-f\d]{2,2})+(:([\d\w]|%[a-fA-f\d]{2,2})+)?@)?([\d\w][-\d\w]{0,253}[\d\w]\.)+[\w]{2,4}(:[\d]+)?(\/([-+_~.\d\w]|%[a-fA-f\d]{2,2})*)*(\?(&amp;?([-+_~.\d\w]|%[a-fA-f\d]{2,2})=?)*)?(#([-+_~.\d\w]|%[a-fA-f\d]{2,2})*)?$/'
            'pattern' => '~^(http|https|ftp)\://[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(:[a-zA-Z0-9]*)?/?([a-zA-Z0-9\-\._\?\,\'/\\\+&amp;%\$#\=\~])*[^\.\,\)\(\s]$~'
		));

		$this->_setValue($value);
		if (!$validator->isValid($value)) {
			$this->_error(self::URL);
			return false;
		}
		return true;
	}
}

