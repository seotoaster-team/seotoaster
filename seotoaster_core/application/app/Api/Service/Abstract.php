<?php
/**
 * RestService_Abstract
 * @author Pavel Kovalyov <pavlo.kovalyov@gmail.com>
 */
abstract class Api_Service_Abstract {

	const REST_STATUS_OK = 200;
	const REST_STATUS_CREATED = 201;
	const REST_STATUS_ACCEPTED = 202;
	const REST_STATUS_NO_CONTENT = 204;
	const REST_STATUS_BAD_REQUEST = 400;
	const REST_STATUS_UNAUTHORIZED = 401;
	const REST_STATUS_FORBIDDEN = 403;
	const REST_STATUS_NOT_FOUND = 404;

	/**
	 * @var Zend_Controller_Request_Http
	 */
	protected $_request;

	/**
	 * @var Zend_Controller_Response_Http
	 */
	protected $_response;

	/**
	 * @var Zend_Controller_Action_Helper_Json
	 */
	protected $_jsonHelper;

	protected $_acl;

    protected $_methodList = array('get', 'post', 'put', 'delete');

    /**
     * Override this property in descendant service to set up custom access list
     *
     * @var array Array with a following structure array('acl_role' => array('allow' => array('get', 'post'), 'deny' => array('put', 'delete')))
     */
    protected $_accessList = array();

	public function __construct(Zend_Controller_Request_Abstract $request, Zend_Controller_Response_Abstract $response){
		$this->setRequest($request)->setResponse($response);

		$this->_jsonHelper      = Zend_Controller_Action_HelperBroker::getStaticHelper('json');
		$this->_jsonHelper->init();
		$this->_initAcl();
		$this->init();
	}

	protected function _initAcl(){
        $acl = $this->getAcl();
        foreach ($this->_methodList as $method) {
            $resourceName = strtolower(get_called_class() . '_' . $method);
            $acl->has($resourceName) || $acl->addResource($resourceName);
            $acl->deny(null, $resourceName);
        }

        // Applying custom access control list
        $this->_applyAccessList($acl);

        Zend_Registry::set('acl', $acl);
	}

	public function init() {
	}

	public function dispatch(){
		$method = strtoupper($this->_request->getMethod());
		if ($method === 'POST' && null !== ($extraMethod = $this->_request->getParam('_method', null))){
			$extraMethod = strtoupper(filter_var($extraMethod, FILTER_SANITIZE_STRING));
			if (in_array($extraMethod, array('PUT', 'DELETE'))){
				$method = $extraMethod;
			}
		}
		$action = strtolower($method).'Action';
		$aclResource = strtolower(get_called_class().'_'.$method);
		if (method_exists($this, $action)){
			if(Tools_Security_Acl::isAllowed($aclResource)){
				return $this->_jsonHelper->direct($this->$action());
			} else {
				$this->_error(null, self::REST_STATUS_FORBIDDEN);
			}
		} else {
			throw new Exceptions_SeotoasterPluginException(get_called_class().' doesn\'t have '.$method.' implemented');
		}
	}

	protected function _error($message = null, $statusCode = self::REST_STATUS_BAD_REQUEST){
		if (is_numeric($statusCode)){
			$statusCode = intval($statusCode);
		}
		$this->_response->clearAllHeaders()->clearBody();
		$this->_response->setHttpResponseCode(intval($statusCode))
						->setHeader('Content-Type', 'application/json', true);
		if (!empty($message)){
			$this->_response->setBody(json_encode($message));
		}

		$this->_response->sendResponse();
		exit();
	}

	/**
	 * The get action handles GET requests and receives an 'id' parameter; it
	 * should respond with the server resource state of the resource identified
	 * by the 'id' value.
	 */
	abstract public function getAction();

	/**
	 * The post action handles POST requests; it should accept and digest a
	 * POSTed resource representation and persist the resource state.
	 */
	abstract public function postAction();

	/**
	 * The put action handles PUT requests and receives an 'id' parameter; it
	 * should update the server resource state of the resource identified by
	 * the 'id' value.
	 */
	abstract public function putAction();

	/**
	 * The delete action handles DELETE requests and receives an 'id'
	 * parameter; it should update the server resource state of the resource
	 * identified by the 'id' value.
	 */
	abstract public function deleteAction();

	public function setResponse($response) {
		$this->_response = $response;
		return $this;
	}

	public function getResponse() {
		return $this->_response;
	}

	public function setRequest($request) {
		$this->_request = $request;
		return $this;
	}

	public function getRequest() {
		return $this->_request;
	}

	public function setAcl($acl) {
		$this->_acl = $acl;
	}

	/**
	 * @return Zend_Acl
	 */
	public function getAcl() {
		return $this->_acl instanceof Zend_Acl ? $this->_acl : Zend_Registry::get('acl');
	}


    /**
     * Apply custom access list for the api service
     *
     * @param Zend_Acl $acl
     */
    protected function _applyAccessList($acl = null) {
        if(!empty($this->_accessList)) {
            $acl = ($acl === null)  ? $this->getAcl() : $acl;
            foreach($this->_accessList as $role => $accessControls) {
                foreach($accessControls as $action => $resources) {
                    if(!empty($resources)) {
                        foreach($resources as $resource) {
                            $resource = strtolower(get_called_class().'_'.$resource);
                            if($acl->has($resource)) {
                                $acl->$action($role, $resource);
                            }
                        }
                    }
                }
            }
            Zend_Registry::set('acl', $acl);
        }
    }
}
