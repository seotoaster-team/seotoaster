<?php

/**
 * Tools
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Tools_Mail_Tools {

	private static $_mailRenderer = null;

	public static function getMailTemplatesHash() {
		$hash          = array();
		$mailTemplates = Application_Model_Mappers_TemplateMapper::getInstance()->findByType(Application_Model_Models_Template::TYPE_MAIL);
		if(!empty ($mailTemplates)) {
			foreach ($mailTemplates as $temlate) {
				$hash[$temlate->getName()] = ucfirst($temlate->getName());
			}
		}
		return $hash;
	}

	public static function sendSignupEmail() {

	}

	public static function sendMailToSiteOwner($mailType) {
		$templateToRender = $mailType . 'mail';
		$renderer         = self::_getMailRenderer();
		$configHlpr       = Zend_Controller_Action_HelperBroker::getStaticHelper('config');
		return self::_sendMail(array(
			'mailTo'   => $configHlpr->getConfig('adminEmail'),
			'mailFrom' => 'Toaster notification ' . $configHlpr->getConfig('adminEmail'),
			'subject'  => 'Notification',
			'body'     => $renderer->render('mailer/' . $templateToRender . '.phtml')
		));

	}

	private static function _sendMail($params) {
		$mailer = new Tools_Mail_Mailer();
		$mailer->setMailTo($params['mailTo']);
		$mailer->setMailFrom($params['mailFrom']);
		$mailer->setSubject($params['subject']);
		$mailer->setBody($params['body']);
		return $mailer->send();
	}

	private static function _getMailRenderer() {
		if(self::$_mailRenderer === null) {
			$websiteHlpr = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
			self::$_mailRenderer = new Zend_View(array(
				'scriptPath' => $websiteHlpr->getPath() . 'seotoaster_core/application/views/scripts/'
			));
		}
		self::$_mailRenderer->websiteUrl = $websiteHlpr->getUrl();
		return self::$_mailRenderer;
	}
}

