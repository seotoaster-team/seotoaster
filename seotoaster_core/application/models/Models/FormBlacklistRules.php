<?php

class Application_Model_Models_FormBlacklistRules extends Application_Model_Models_Abstract
{

    const RULE_TYPE_DOMAIN = 'domain';

    const RULE_TYPE_IP_ADDRESS = 'ipaddress';

    const RULE_TYPE_EMAIL = 'email';

    protected $_type = '';

    protected $_value = '';

    /**
     * @return string
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * @param string $type
     * @return string
     */
    public function setType($type)
    {
        $this->_type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->_value;
    }

    /**
     * @param string $value
     * @return string
     */
    public function setValue($value)
    {
        $this->_value = $value;

        return $this;
    }


}

