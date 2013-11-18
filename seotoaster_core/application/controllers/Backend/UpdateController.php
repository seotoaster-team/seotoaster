<?php
/**
 * UpdateController - handler for upadate
 *
 * @author Vitaly Vyrodov <vitaly.vyrodov@gmail.com>
 * @todo   : response helper
 */
class Backend_UpdateController extends Zend_Controller_Action {
    public function init() {
        parent::init();
        if(!Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_USERS)) {
            $this->_redirect($this->_helper->website->getUrl(), array('exit' => true));
        }
    }

    public function versionAction(){
        try {
            $system['fs'] = Tools_Filesystem_Tools::getFile('version.txt');
            $system['sf'] =  Tools_Filesystem_Tools::getFile('plugins/shopping/version.txt');
            $client = new Zend_Http_Client( 'http://seotoaster.com/version.txt' );
            $response = $client->request();
            $remote['fs'] = $response->getBody();
            $client->setUri('http://seotoaster.com/version.txt');
            $response = $client->request();
            $remote['sf'] = $response->getBody();
            return $system;
        } catch (Exceptions_SeotoasterException $se) {
            if(self::debugMode()) {
                error_log($se->getMessage());
            }
        }
        return '';
    }





}
