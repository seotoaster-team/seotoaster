<?php
/**
 * TriggerAction model
 * @author Pavel Kovalyov <pavlo.kovalyov@gmail.com>
 */
class Application_Model_Models_TriggerAction extends Application_Model_Models_Abstract {

	protected $triggerName;

	protected $templateName;

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

	public function setTemplateName($templateName) {
		$this->templateName = $templateName;
	}

	public function getTemplateName() {
		return $this->templateName;
	}

	public function setTriggerName($triggerName) {
		$this->triggerName = $triggerName;
	}

	public function getTriggerName() {
		return $this->triggerName;
	}
}
