<?php

class Tools_System_SystemNotifications {

    const OPTIMIZED_NOTIFICATIONS = 'optimizedNotifications';

    private static $_htmlRenderer = null;

    /**
     * @return Zend_View|null
     */
    private static function _getHtmlRenderer()
    {
        if(self::$_htmlRenderer === null) {
            $websiteHlpr = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
            self::$_htmlRenderer = new Zend_View(array(
                'scriptPath' => $websiteHlpr->getPath() . 'seotoaster_core/application/views/scripts/backend/systemnotifications/'
            ));
        }
        self::$_htmlRenderer->websiteUrl = $websiteHlpr->getUrl();
        return self::$_htmlRenderer;
    }

    /**
     * Main render method
     *
     * @param $obj
     * @param $type
     * @return mixed
     */
    public static function sendSystemNotification($obj, $type, $emailSubjectMessage = '')
    {
        if(!empty($type)) {
            $methodName = '_render' . ucfirst($type);

            if (method_exists(__CLASS__, $methodName)) {
               return self::$methodName($obj, $emailSubjectMessage);
            }
        }
    }

    private static function _renderRevokeOptimization($obj, $emailSubjectMessage) {
        if($obj instanceof Application_Model_Models_Page) {
           $renderer         = self::_getHtmlRenderer();

           $renderer->pageUrl = $obj->getUrl();
           $renderer->pageName = $obj->getNavName();

           $optimizedEmailBody = $renderer->render('optimizedemail.phtml');
       }

       if(!empty($optimizedEmailBody)) {
           self::sendEmails(self::OPTIMIZED_NOTIFICATIONS, $optimizedEmailBody, $emailSubjectMessage);
       }
    }

    public static function sendEmails($param, $notificationContent, $emailSubjectMessage)
    {
        if(!empty($param) && !empty($notificationContent)) {
            $configHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('config');
            $translator = Zend_Registry::get('Zend_Translate');

            $currentConfigParams = $configHelper->getConfig($param);

            if(!empty($currentConfigParams)) {
                $bccEmails = explode(',', $currentConfigParams);
                $validBssEmails = array();

                if(is_array($bccEmails) && !empty($bccEmails)) {
                    $emailValidation = new Tools_System_CustomEmailValidator();
                    foreach($bccEmails as $email){
                        if(!$emailValidation->isValid(trim($email))){
                            continue;
                        }
                        array_push($validBssEmails, $email);
                    }
                }

                if(!empty($validBssEmails)) {
                    $adminEmail = $configHelper->getConfig('adminEmail');

                    $adminEmail = !empty($adminEmail) ? $adminEmail : 'admin@localhost';
                    $emailSubjectMessage = !empty($emailSubjectMessage) ? $emailSubjectMessage : 'System notifications';

                    $mailer   = Tools_Mail_Tools::initMailer();
                    $subject = $translator->translate($emailSubjectMessage);
                    $mailer->setMailFrom($adminEmail);
                    $mailer->setMailTo($validBssEmails);
                    $mailer->setBody($notificationContent);
                    $mailer->setSubject($subject);

                    try {
                        $mailer->send();
                        return true;
                    } catch (Exception $e) {
                        return false;
                    }
                }
            }
        }
        return false;
    }

}