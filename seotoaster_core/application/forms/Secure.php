<?php

/**
 * Add zend form hash element
 * Class Application_Form_Secure
 */
class Application_Form_Secure extends Zend_Form
{
    public function init()
    {
        $this->addElement( 'hash', Tools_System_Tools::CSRF_SECURE_TOKEN, array(
            'ignore'  => true,
            'timeout' => 1440
        ) );
    }
}
