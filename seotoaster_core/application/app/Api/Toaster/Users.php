<?php
/**
 * Users.php
 * @author Pavel Kovalyov <pavlo.kovalyov@gmail.com>
 */

class Api_Toaster_Users extends Api_Service_Abstract
{

    /**
     * @var Helpers_Action_Session
     */
    private $_sessionHelper;

    /**
     * @var array Access Control List
     */
    protected $_accessList = array(
        Tools_Security_Acl::ROLE_SUPERADMIN => array(
            'allow' => array('get', 'post', 'put', 'delete')
        ),
        Tools_Security_Acl::ROLE_ADMIN => array(
            'allow' => array('get', 'post', 'put', 'delete')
        ),
        Tools_Security_Acl::ROLE_MEMBER => array(
            'allow' => array('get', 'put', 'delete')
        ),
        Tools_Security_Acl::ROLE_USER => array(
            'allow' => array('get', 'put')
        ),
        // TODO: Think about interaction with roles for shopping
        Tools_Security_Acl::ROLE_GUEST => array(
            'allow' => array('get', 'put')
        )
    );

    public function init()
    {
        parent::init();
        $this->_sessionHelper = Zend_Controller_Action_HelperBroker::getExistingHelper('session');
        $acl = $this->getAcl();
    }


    /**
     * The get action handles GET requests and receives an 'id' parameter; it
     * should respond with the server resource state of the resource identified
     * by the 'id' value.
     */
    public function getAction()
    {
        // TODO: Implement getAction() method.
    }

    /**
     * The post action handles POST requests; it should accept and digest a
     * POSTed resource representation and persist the resource state.
     */
    public function postAction()
    {
        // TODO: Implement postAction() method.
    }

    /**
     * The put action handles PUT requests and receives an 'id' parameter; it
     * should update the server resource state of the resource identified by
     * the 'id' value.
     */
    public function putAction()
    {
        $id = intval(filter_var($this->_request->getParam('id'), FILTER_VALIDATE_INT));
        $data = json_decode($this->_request->getRawBody(), true);

        if ($id && !empty($data)) {
            if (!Tools_Security_Acl::isAllowed(
                    Tools_Security_Acl::RESOURCE_USERS
                ) && $id !== $this->_sessionHelper->getCurrentUser()->getId()
            ) {
                $this->_error(self::REST_STATUS_FORBIDDEN);
            }

            $user = Application_Model_Mappers_UserMapper::getInstance()->find($id);

            if ($user instanceof Application_Model_Models_User) {

                Application_Model_Mappers_UserMapper::getInstance()->loadUserAttributes($user);

                foreach ($data as $attribute => $value) {
                    $setter = 'set' . ucfirst(strtolower($attribute));
                    if (method_exists($user, $setter)) {
                        $user->$setter($value);
                    } else {
                        $user->setAttribute($attribute, $value);
                    }
                }
                $user->setPassword(false);
                Application_Model_Mappers_UserMapper::getInstance()->save($user);
                $mailWatchdog = new Tools_Mail_Watchdog(array(
                    'trigger'  => Tools_Mail_SystemMailWatchdog::TRIGGER_USERCHANGEATTR,
                ));
                $mailWatchdog->notify($user);
                return array('status' => 'ok');
            }
        }
    }

    /**
     * The delete action handles DELETE requests and receives an 'id'
     * parameter; it should update the server resource state of the resource
     * identified by the 'id' value.
     */
    public function deleteAction()
    {
        // TODO: Implement deleteAction() method.
    }

}