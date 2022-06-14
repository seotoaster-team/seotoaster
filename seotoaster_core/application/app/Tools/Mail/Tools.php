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

    public static function getAutoReplyPdfTemplatesHash() {
        $hash          = array();
        $mailTemplates = Application_Model_Mappers_TemplateMapper::getInstance()->findByType(Application_Model_Models_Template::TYPE_PDF_AUTO_REPLY);
        if(!empty ($mailTemplates)) {
            foreach ($mailTemplates as $temlate) {
                $hash[$temlate->getName()] = ucfirst($temlate->getName());
            }
        }
        return $hash;
    }

    /**
     * Prepare auto reply attachment pdf
     *
     * @param string $autoReplyPdfTemplate auto reply form template name
     * @param array $formParams form params
     * @return  string
     */
    public static function prepareAutoReplyAttachmentPdf($autoReplyPdfTemplate, $formParams)
    {

        $templateModel = Application_Model_Mappers_TemplateMapper::getInstance()->find($autoReplyPdfTemplate);
        if (!$templateModel instanceof Application_Model_Models_Template) {
            return false;
        }

        $websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
        $websiteConfig = Zend_Controller_Action_HelperBroker::getStaticHelper('config')->getConfig();
        $themeData = Zend_Registry::get('theme');

        $pdfTmpPath = $websiteConfig['path'] . 'plugins' . DIRECTORY_SEPARATOR . 'invoicetopdf' . DIRECTORY_SEPARATOR . 'invoices' . DIRECTORY_SEPARATOR;
        if (!file_exists($pdfTmpPath)) {
            return false;
        }

        require_once($websiteConfig['path'] . 'plugins' . DIRECTORY_SEPARATOR . 'invoicetopdf' . DIRECTORY_SEPARATOR.'system/library/mpdflatest/vendor/autoload.php');

        $pdfFile = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'tempDir' => $pdfTmpPath
        ]);

        $parserOptions = array(
            'websiteUrl' => $websiteHelper->getUrl(),
            'websitePath' => $websiteHelper->getPath(),
            'currentTheme' => $websiteHelper->getConfig('currentTheme'),
            'themePath' => $themeData['path'],
        );

        $parser = new Tools_Content_Parser($templateModel->getContent(), array(), $parserOptions);
        $content = $parser->parse();
        $entityParser  = new Tools_Content_EntityParser();

        $formDetails = self::cleanFormParams($formParams);
        $lexemePrefix = 'form';

        $paramsDictionary = array();
        foreach ($formDetails as $paramName => $paramValue) {
            $paramsDictionary[$lexemePrefix . ':' . $paramName] = $paramValue;
        }

        $entityParser->addToDictionary($paramsDictionary);
        $content = $entityParser->parse($content);

        $pdfFile->WriteHTML($content);
        $pdfFileName = 'autoreply_'.sha1(microtime()).'.pdf';

        $filePath = $websiteHelper->getPath() . $websiteHelper->getTmp() . $pdfFileName;
        $pdfFile->Output($filePath, 'F');

        $attachment = new Zend_Mime_Part(file_get_contents($filePath));
        $attachment->type = 'application/pdf';
        $attachment->disposition = Zend_Mime::DISPOSITION_ATTACHMENT;
        $attachment->encoding = Zend_Mime::ENCODING_BASE64;
        $attachment->filename = $pdfFileName;

        return array('attachment' => $attachment, 'filePath' => $filePath);

    }

    public static function cleanFormParams($data)
    {
        unset($data['controller']);
        unset($data['action']);
        unset($data['module']);
        unset($data['formName']);
        unset($data['captcha']);
        unset($data['captchaId']);

        return $data;
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

