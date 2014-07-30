<?php
/**
 * Base.php
 * @author Pavel Kovalyov <pavlo.kovalyov@gmail.com>
 */

class Widgets_User_Base extends Widgets_Abstract {

    const USERPIC_FOLDER = 'userpics';

    /**
     * @var Helpers_Action_Session
     */
    protected $_sessionHelper;

    /**
     * @var Helpers_Action_Website
     */
    protected $_websiteHelper;

    /**
     * @var Application_Model_Models_User
     */
    protected $_user;

    protected $_cacheable = false;

    protected $_editableMode = false;

    protected function _init() {
        parent::_init();
        $this->_sessionHelper = Zend_Controller_Action_HelperBroker::getExistingHelper('session');
        $this->_websiteHelper = Zend_Controller_Action_HelperBroker::getExistingHelper('website');
//        $this->_view = new Zend_View(array(
//            'scriptPath' => dirname(__FILE__) . '/views'
//        ));
        $this->_view = clone Zend_Layout::getMvcInstance()->getView();
        $this->_view->addScriptPath(dirname(__FILE__) . '/views');
        $this->_view->websiteUrl = $this->_websiteHelper->getUrl();
    }


    protected function _load(){
        if (empty($this->_options)) {
            throw new Exceptions_SeotoasterWidgetException('No options provided');
        }

        if (in_array('readonly', $this->_options)) {
            $this->_view->readonly = $this->_options[1];
            unset($this->_options[1]);
        }
        if (is_numeric(reset($this->_options))) {
            $userId = array_shift($this->_options);
            $this->_user = Application_Model_Mappers_UserMapper::getInstance()->find($userId);
            if (is_null($this->_user)){
                return '';
            }
        } elseif ($this->_sessionHelper->getCurrentUser()->getRoleId() === Tools_Security_Acl::ROLE_GUEST) {
            return '';
        } else {
            $this->_user = $this->_sessionHelper->getCurrentUser();
        }
        $this->_user->loadAttributes();

        if (Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_USERS)
                || $this->_user->getId() === $this->_sessionHelper->getCurrentUser()->getId()) {
            $this->_editableMode = true;
            Zend_Layout::getMvcInstance()->getView()->headScript()->appendFile(
                $this->_websiteHelper->getUrl() . 'system/js/internal/user-attributes.js'
            );
        }

        $method = strtolower(array_shift($this->_options));
        try {
            return $this->{'_render' . ucfirst($method)}();
        } catch (Exception $e) {
            return '<b>Method ' . $method . ' doesn\'t exist</b>';
        }
    }

    public function __call($attrName, $arguments) {
        if (preg_match('/^_render/', $attrName)) {
            $attrName = mb_strtolower(mb_strcut($attrName, 7));
            if (!empty($this->_options)) {
                $attrName = array_merge(array($attrName), $this->_options);
                $attrName = implode('_', $attrName);
            }
            $attrName = preg_replace('/[^\w\d-_]/ui', '', $attrName);

            // check if we have a getter for this property
            $getter = 'get'.ucfirst($attrName);
            if (method_exists($this->_user, $getter)){
                $value = $this->_user->$getter();
            } else {
                // or try to get attribute value
                $value = $this->_user->getAttribute($attrName);
            }

            if ($this->_editableMode) {
                $this->_view->attribute = $attrName;
                $this->_view->value = $value;
                $this->_view->userId = $this->_user->getId();
                return $this->_view->render('user-attribute.phtml');
            }
            return $value;
        }
    }

    protected function _renderPhoto() {
        if (isset($this->_options[0])) {
            $imgSize = $this->_options[0];
        } else {
            $imgSize = 'small';
        }
        $imgNameSlug = md5($this->_user->getId().$this->_user->getEmail()).'.png';
        $userpic = 'media/'.self::USERPIC_FOLDER.'/'.$imgSize.'/'.$imgNameSlug;
        $this->_view->userpic = file_exists($this->_websiteHelper->getPath().$userpic) ? $userpic : null ;
        $this->_view->imgNameSlug = $imgNameSlug;
        $this->_view->user = $this->_user;
        $this->_view->imgSize = $imgSize;
        $this->_view->editableMode = $this->_editableMode;

        return $this->_view->render('user-photo.phtml');
    }

}