<?php
/**
 * Toaster currency helper
 *
 */

class  Zend_View_Helper_ToasterCurrency extends Zend_View_Helper_Abstract {

	public function toasterCurrency($value, $locale = 'en_US') {
		$configHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('config');
		$currency     = new Zend_Currency(null, $locale);
		$currency->setFormat(array(
			'display' => Zend_Currency::NO_SYMBOL
		));
		return $currency->toCurrency((float) $value);
	}

}
