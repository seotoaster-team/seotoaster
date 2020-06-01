<?php

class Application_Form_Form extends Application_Form_Secure
{

    protected $_code = '';

    protected $_contactEmail = '';

    protected $_messageSuccess = '';

    protected $_messageError = '';

    protected $_replyFrom = '';

    protected $_replySubject = '';

    protected $_trackingCode = '';

    protected $_replyMailTemplate = '';

    protected $_replyText = '';

    protected $_name = '';

    protected $_adminSubject = '';

    protected $_adminMailTemplate = '';

    protected $_adminFrom = '';

    protected $_adminFromName = '';

    protected $_adminText = '';

    protected $_id = null;

    protected $_replyEmail = 0;

    public function init()
    {
        parent::init();
        $this->setMethod(Zend_Form::METHOD_POST);

        $this->addElement(
            new Zend_Form_Element_Textarea(array(
                'id' => 'code',
                'class' => 'code-area',
                'name' => 'code',
                'label' => 'Form code',
                'value' => $this->_code,
                'cols' => '45',
                'rows' => '4',
                'required' => true,
                'filters' => array('StringTrim')
            ))
        );

        $this->addElement(
            new Zend_Form_Element_Text(array(
                'id' => 'contact-mail',
                'name' => 'contactEmail',
                'label' => 'Lead delivery email',
                'value' => $this->_contactEmail,
                'required' => true,
                'filters' => array('StringTrim')
            ))
        );

        $this->addElement(
            new Zend_Form_Element_Text(array(
                'id' => 'reply-from-name',
                'name' => 'replyFromName',
                'label' => 'Auto reply from name',
                'value' => $this->_replyFromName,
                'required' => false,
                'filters' => array('StringTrim', new Zend_Filter_StripTags())
            ))
        );

        $this->addElement(
            new Zend_Form_Element_Text(array(
                'id' => 'reply-from',
                'name' => 'replyFrom',
                'label' => 'Auto reply from email',
                'value' => $this->_replyFrom,
                'required' => true,
                'filters' => array('StringTrim')
            ))
        );

        $this->addElement(
            new Zend_Form_Element_Select(array(
                'id' => 'reply-mail-template',
                'name' => 'replyMailTemplate',
                'label' => 'Auto reply mail template',
                'value' => $this->_replyMailTemplate,
                //'required'   => true,
                'registerInArrayValidator' => false
            ))
        );

        $this->addElement(
            new Zend_Form_Element_Text(array(
                'id' => 'success-message',
                'name' => 'messageSuccess',
                'label' => 'Success Message',
                'value' => $this->_successMessage,
                'required' => true,
                'filters' => array('StringTrim')
            ))
        );

        $this->addElement(
            new Zend_Form_Element_Text(array(
                'id' => 'error-message',
                'name' => 'messageError',
                'label' => 'Error Message',
                'value' => $this->_errorMessage,
                'required' => true,
                'filters' => array('StringTrim')
            ))
        );

        $this->addElement(
            new Zend_Form_Element_Text(array(
                'id' => 'reply-subject',
                'name' => 'replySubject',
                'label' => 'Auto reply subject',
                'value' => $this->_replySubject,
                'required' => true,
                'filters' => array('StringTrim'),

            ))
        );

        $this->addElement(
            new Zend_Form_Element_Text(array(
                'id' => 'admin-subject',
                'name' => 'adminSubject',
                'label' => 'Lead delivery subject',
                'value' => $this->_adminSubject,
                'required' => false,
                'filters' => array('StringTrim'),
            ))
        );

        $this->addElement(
            new Zend_Form_Element_Select(array(
                'id' => 'admin-mail-template',
                'name' => 'adminMailTemplate',
                'label' => 'Lead delivery mail template',
                'value' => $this->_adminMailTemplate,
                'required' => false,
                'registerInArrayValidator' => false
            ))
        );

        $this->addElement(
            new Zend_Form_Element_Text(array(
                'id' => 'admin-from',
                'name' => 'adminFrom',
                'label' => 'Lead delivery from email',
                'value' => $this->_adminFrom,
                'required' => true,
                'filters' => array('StringTrim')
            ))
        );

        $this->addElement(
            new Zend_Form_Element_Text(array(
                'id' => 'admin-from-name',
                'name' => 'adminFromName',
                'label' => 'Lead delivery from name',
                'value' => $this->_adminFromName,
                'required' => false,
                'filters' => array('StringTrim', new Zend_Filter_StripTags())
            ))
        );

        $this->addElement(
            new Zend_Form_Element_Textarea(array(
                'id' => 'admin-text',
                'class' => 'code-area',
                'name' => 'adminText',
                'label' => 'Lead delivery text',
                'value' => $this->_adminText,
                'cols' => '45',
                'rows' => '2',
                'filters' => array('StringTrim')
            ))
        );


        $this->addElement(
            new Zend_Form_Element_Textarea(array(
                'id' => 'tracking-code',
                'name' => 'trackingCode',
                'label' => 'Conversion tracking code',
                'value' => $this->_trackingCode,
                'cols' => '45',
                'rows' => '4',
                'filters' => array('StringTrim')
            ))
        );

        $this->addElement(
            new Zend_Form_Element_Textarea(array(
                'id' => 'reply-text',
                'class' => 'code-area',
                'name' => 'replyText',
                'label' => 'Reply text',
                'value' => $this->_replyText,
                'cols' => '45',
                'rows' => '2',
                'filters' => array('StringTrim')
            ))
        );

        $this->addElement(
            new Zend_Form_Element_Checkbox(array(
                'id' => 'reply-email',
                'name' => 'replyEmail',
                'label' => 'Don\'t send reply email',
                'value' => $this->_replyEmail,
            ))
        );

        $this->addElement(
            new Zend_Form_Element_Hidden(array(
                'id' => 'form-name',
                'name' => 'name',
                'value' => $this->_formName
            ))
        );

        $this->addElement(
            new Zend_Form_Element_Text(array(
                'id' => 'mobile',
                'name' => 'mobile',
                'placeholder' => 'Never lose another opportunity',
                'label' => 'Add your mobile to also receive lead details via SMS',
                'value' => $this->_mobile,
                'filters' => array('StringTrim'),
            ))
        );

        $this->addElement(
            new Zend_Form_Element_Checkbox(array(
                'id' => 'enable-sms',
                'name' => 'enableSms',
                'label' => 'Send the reply text as an sms to the user',
                'value' => $this->_enableSms,
            ))
        );

        $this->addElement(
            new Zend_Form_Element_Hidden(array(
                'id' => 'form-id',
                'name' => 'id',
                'value' => $this->_id
            ))
        );

        $this->addElement(
            'button',
            'submit',
            array(
                'label' => 'Save',
                'type' => 'submit',
                'class' => 'btn ticon-save grid_3',
                'escape' => false
            )
        );

        $this->setElementDecorators(array('ViewHelper', 'Label'));
    }
}