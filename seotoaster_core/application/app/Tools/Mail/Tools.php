<?php

/**
 * Tools
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Tools_Mail_Tools {

	private static $_mailRenderer = null;

    /**
     * Initialize toaster mailer with valid transport
     *
     * @static
     * @return Tools_Mail_Mailer Seotoaster mailer instance
     */
    public static function initMailer(){
        $config        = Zend_Controller_Action_HelperBroker::getStaticHelper('config')->getConfig();
        $mailer = new Tools_Mail_Mailer();

        if ((bool)$config['useSmtp']){
            $smtpConfig = array(
                'host'      => $config['smtpHost'],
                'username'  => $config['smtpLogin'],
                'password'  => $config['smtpPassword']
            );
            if ((bool)$config['smtpSsl']){
                $smtpConfig['ssl'] = $config['smtpSsl'];
            }
            if (!empty($config['smtpPort'])){
                $smtpConfig['port'] = $config['smtpPort'];
            }
            $mailer->setSmtpConfig($smtpConfig);
            $mailer->setTransport(Tools_Mail_Mailer::MAIL_TYPE_SMTP);
        } else {
            $mailer->setTransport(Tools_Mail_Mailer::MAIL_TYPE_MAIL);
        }
        return $mailer;
    }

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

