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
            'pattern' => '~^(ht|f)tps?\://((\d{1,3}\.){3}\d{1,3}|([\w-]*\.){1,}[a-z]{2,10})(:\d{2,5})?(|/([\w-.?,/+&%$#=\~])*(?<![.,]))$~'
		));

		$this->_setValue($value);
		if (!$validator->isValid($value)) {
			$this->_error(self::URL);
			return false;
		}
		return true;
	}
}

