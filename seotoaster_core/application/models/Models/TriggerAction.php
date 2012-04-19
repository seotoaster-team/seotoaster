<?php
/**
 * TriggerAction model
 * @author Pavel Kovalyov <pavlo.kovalyov@gmail.com>
 */
class Application_Model_Models_TriggerAction extends Application_Model_Models_Abstract {

	protected $trigger;

	protected $template;

	protected $recipient;

	protected $message;


	public function setMessage($message) {
		$this->message = $message;
	}

	public function getMessage() {
		return $this->message;
	}

	public function setRecipient($recipient) {
		$this->recipient = $recipient;
	}

	public function getRecipient() {
		return $this->recipient;
	}

	public function setTemplate($templateName) {
		$this->template = $templateName;
	}

	public function getTemplate() {
		return $this->template;
	}

	public function setTrigger($triggerName) {
		$this->trigger = $triggerName;
	}

	public function getTrigger() {
		return $this->trigger;
	}
}
