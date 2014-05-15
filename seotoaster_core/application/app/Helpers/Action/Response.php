<?php

/**
 * Send json ecoded response
 *
 * @author SeoToaster Dev Team
 */
class Helpers_Action_Response extends Zend_Controller_Action_Helper_Abstract {

	const SUCCESS = 'success';

	const FAIL    = 'fail';

	public function success($body = '') {
		if(!$body) {
			$body = self::SUCCESS;
		}
		$this->_response($body);
	}

	public function fail($body = '') {
		if(!$body) {
			$body = self::FAIL;
		}
		$this->_response($body, 1);
	}

	public function notFound($body = '') {
		if(!$body) {
			$body = 'Ooops. Cannot find this page.';
		}
		
		$this->getResponse()
			->setHttpResponseCode(404)
			->setBody($body)
			->setHeader('HTTP/1.1', '404 Not Found')
			->setHeader('Status', '404 File not found')
			->sendResponse();
		exit;
	}

	public function response($body, $error = 0, $code = 200, $headers = '') {
		$this->_response($body, $error, $code, $headers);
	}

	private function _response($body, $error = 0, $code = 200, $headers = '') {
		$responseData = Zend_Json::encode(array(
			'error'        => $error,
			'responseText' => $body,
			'httpCode'     => $code
		));
		$response = $this->getResponse();
		$response->setHttpResponseCode($code)
			->setBody($responseData)
            ->setHeader('Content-Type', 'application/json', true);

		if($headers) {
			if(is_array($headers) && !empty ($headers)){
				foreach ($headers as $key => $val) {
					$response->setHeader($key, $val);
				}
			}
			else {
				$response->setRawHeader($headers);
			}
		}

		$response->sendResponse();
		exit;
	}

}

