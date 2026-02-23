<?php

class Api_Toaster_Actionemailgridinfo extends Api_Service_Abstract
{


    /**
     * Lead secure token
     */
    const ACTION_EMAIL_SECURE_TOKEN = 'ActionEmails';

    /**
     * System response helper
     *
     * @var null
     */
    protected $_responseHelper = null;

    /**
     * @var array Access Control List
     */
    protected $_accessList = array(
        Tools_Security_Acl::ROLE_SUPERADMIN => array(
            'allow' => array('get', 'post', 'put', 'delete')
        ),
        Tools_Security_Acl::ROLE_ADMIN => array(
            'allow' => array('get', 'post', 'put', 'delete')
        )
    );

    public function init()
    {
        parent::init();
        $this->_responseHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('response');
    }


    /**
     * Leads grid info
     *
     * Resource:
     * : /api/toaster/actionemailgridinfo/
     *
     * HttpMethod:
     * : GET
     *
     * @return JSON
     */
    public function getAction()
    {
        $id = $this->_request->getParam('id');

        $where = null;

        $pluginsTriggers = Tools_Plugins_Tools::fetchFromConfigIniData();
        $systemTriggers = Tools_System_Tools::fetchSystemtriggers();
        $triggersLabels = Tools_Plugins_Tools::fetchFromConfigIniData('actionEmailLabel');
        $triggers = is_array($pluginsTriggers) ? array_merge($systemTriggers, $pluginsTriggers) : $systemTriggers;

        $data = array();
        $data['data'] = array();
        $data['additionalInfo'] = array();

        if (!empty($id)) {
            $services = array('email' => 'e-mail', 'sms' => 'sms');
            $recipients = Application_Model_Mappers_EmailTriggersMapper::getInstance()->getReceivers(true);
            $recipients = array_combine($recipients, $recipients);
            $mailTemplates = Tools_Mail_Tools::getMailTemplatesHash();
            $actions = Application_Model_Mappers_EmailTriggersMapper::getInstance()->fetchArray();
            $data['id'] = $id;
        }

        $actionsOptions = array_combine(array_keys($triggers), array_map(function ($trigger) {
            return str_replace('-', ' ', ucfirst($trigger));
        }, array_keys($triggers)));

        if (!empty($triggersLabels)) {
            foreach ($actionsOptions as $key => $option) {
                if (array_key_exists($key, $triggersLabels) && !empty($triggersLabels[$key]['label'])) {
                    $actionsOptions[$key] = $triggersLabels[$key]['label'];
                }
            }
        }

        $presortedActionOptions = array();

        foreach ($actionsOptions as $key => $label) {
            $presortedActionOptions[] = array(
                'key'   => $key,
                'label' => $label
            );
        }

        $data['additionalInfo']['actionsOptions'] = $actionsOptions;
        $data['additionalInfo']['presortedActionOptions'] = $presortedActionOptions;

        return $data;
    }

    /**
     *
     * Resource:
     *
     * HttpMethod:
     * : POST
     *
     * @return JSON
     */
    public function postAction()
    {

    }

    /**
     *
     * Resource:
     *
     * HttpMethod:
     * : PUT
     *
     * ## Parameters:
     * id (source integer)
     *
     * @return JSON
     */
    public function putAction()
    {

    }

    /**
     *
     * Resource:
     *
     * HttpMethod:
     * : DELETE
     *
     * ## Parameters:
     * id (source integer)
     *
     * @return JSON
     */
    public function deleteAction()
    {


    }

}
