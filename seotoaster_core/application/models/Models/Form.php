<?php

/**
 * Form model
 *
 * @author Seotoaster Dev Team
 */
class Application_Model_Models_Form extends Application_Model_Models_Abstract
{

    protected $_name = '';

    protected $_code = '';

    protected $_contactEmail = '';

    protected $_messageSuccess = '';

    protected $_messageError = '';

    protected $_replyFrom = '';

    protected $_replyFromName = '';

    protected $_replySubject = '';

    protected $_replyMailTemplate = '';

    protected $_replyText = '';

    protected $_captcha = 0;

    protected $_mobile = '';

    protected $_enableSms = 0;

    public function getId()
    {
        return $this->_id;
    }

    public function setId($id)
    {
        $this->_id = $id;
        return $this;
    }

    public function getName()
    {
        return $this->_name;
    }

    public function setName($name)
    {
        $this->_name = $name;
        return $this;
    }

    public function getCode()
    {
        return $this->_code;
    }

    public function setCode($code)
    {
        $this->_code = $code;
        return $this;
    }

    public function getContactEmail()
    {
        return $this->_contactEmail;
    }

    public function setContactEmail($contactEmail)
    {
        $this->_contactEmail = $contactEmail;
        return $this;
    }

    public function getMessageSuccess()
    {
        return $this->_messageSuccess;
    }

    public function setMessageSuccess($messageSuccess)
    {
        $this->_messageSuccess = $messageSuccess;
        return $this;
    }

    public function getMessageError()
    {
        return $this->_messageError;
    }

    public function setMessageError($messageError)
    {
        $this->_messageError = $messageError;
        return $this;
    }

    public function getReplyFrom()
    {
        return $this->_replyFrom;
    }

    public function setReplyFrom($replyFrom)
    {
        $this->_replyFrom = $replyFrom;
        return $this;
    }

    public function getReplyFromName()
    {
        return $this->_replyFromName;
    }

    public function setReplyFromName($replyFromName)
    {
        $this->_replyFromName = $replyFromName;
        return $this;
    }

    public function getReplySubject()
    {
        return $this->_replySubject;
    }

    public function setReplySubject($replySubject)
    {
        $this->_replySubject = $replySubject;
        return $this;
    }

    public function getReplyMailTemplate()
    {
        return $this->_replyMailTemplate;
    }

    public function setReplyMailTemplate($replyMailTemplate)
    {
        $this->_replyMailTemplate = $replyMailTemplate;
        return $this;
    }

    public function setReplyText($replyText)
    {
        $this->_replyText = $replyText;
        return $this;
    }

    public function getReplyText()
    {
        return $this->_replyText;
    }

    public function setCaptcha($captcha)
    {
        $this->_captcha = $captcha;
        return $this;
    }

    public function getCaptcha()
    {
        return $this->_captcha;
    }

    public function setEnableSms($enableSms)
    {
        $this->_enableSms = $enableSms;
        return $this;
    }

    public function getEnableSms()
    {
        return $this->_enableSms;
    }

    public function setMobile($mobile)
    {
        $this->_mobile = $mobile;
        return $this;
    }

    public function getMobile()
    {
        return $this->_mobile;
    }


}