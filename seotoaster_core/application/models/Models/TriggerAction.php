<?php
/**
 * TriggerAction model
 * @author Pavel Kovalyov <pavlo.kovalyov@gmail.com>
 */
class Application_Model_Models_TriggerAction extends Application_Model_Models_Abstract {

    const SERVICE_TYPE_EMAIL = 'email';

    const SERVICE_TYPE_SMS = 'sms';

	protected $_trigger;

	protected $_template;

	protected $_recipient;

	protected $_message;

    protected $_smsText;

    protected $_from    = '';

    protected $_subject = '';

    protected $_service;

    protected $_preheader;


	public function setMessage($message) {
		$this->_message = $message;
        return $this;
	}

	public function getMessage() {
		return $this->_message;
	}

    public function setSmsText($smsText) {
        if($this->_service === self::SERVICE_TYPE_SMS) {
            $this->setMessage($smsText);
        }
        return $this;
    }

    public function getSmsText() {
        return $this->_smsText;
    }

	public function setRecipient($recipient) {
		$this->_recipient = $recipient;
        return $this;
	}

	public function getRecipient() {
		return $this->_recipient;
	}

	public function setTemplate($templateName) {
		$this->_template = $templateName;
        return $this;
	}

	public function getTemplate() {
		return $this->_template;
	}

	public function setTrigger($triggerName) {
		$this->_trigger = $triggerName;
        return $this;
	}

	public function getTrigger() {
		return $this->_trigger;
	}

    public function setFrom($from) {
        $this->_from = $from;
        return $this;
    }

    public function getFrom() {
        return $this->_from;
    }

    public function setSubject($subject) {
        $this->_subject = $subject;
        return $this;
    }

    public function getSubject() {
        return $this->_subject;
    }

    public function setService($service) {
        $this->_service = $service;
        return $this;
    }

    public function getService() {
        return $this->_service;
    }

    public function unsetSmsTextProperty() {
        unset($this->_smsText);
    }

    /**
     * @return mixed
     */
    public function getPreheader()
    {
        return $this->_preheader;
    }

    /**
     * @param mixed $preheader
     */
    public function setPreheader($preheader)
    {
        $this->_preheader = $preheader;
        return $this;
    }

}
