<?php
/**
 * Prepop widget
 *
 * @author: iamne <eugene@seotoaster.com> Seotoaster core team
 * Date: 5/24/12
 * Time: 3:58 PM
 */
class Widgets_Prepop_Prepop extends Widgets_AbstractContent {

    const TYPE_TEXT     = 'text';

    const TYPE_TEXTAREA = 'textarea';

    const TYPE_CHECKBOX = 'checkbox';

    const TYPE_SELECT   = 'select';

    const TYPE_RADIO    = 'radio';

    protected $_prepopName        = '';

    protected $_prepopContent     = null;

    protected $_prepopContainerId = null;

    protected $_cacheable         = false;

    protected function _init() {
        parent::_init();
        $this->_prepopName          = array_shift($this->_options);
        $this->_view                = new Zend_View(array(
            'scriptPath' => __DIR__ . '/views'
        ));
        $this->_view->prepopName    = $this->_prepopName;
        $this->_view->websiteUrl    = Zend_Controller_Action_HelperBroker::getStaticHelper('website')->getUrl();
        $this->_view->commonOptions = array(
            'pageId'        => $this->_toasterOptions['id'],
            'containerType' => Application_Model_Models_Container::TYPE_PREPOP,
            'containerName' => $this->_prepopName
        );
    }

    protected function  _load() {
        if(!isset($this->_options[0])) {
            throw new Exceptions_SeotoasterWidgetException('Not enough parameters for the widget <strong>prepop</strong>.');
        }

        $prepop = Application_Model_Mappers_ContainerMapper::getInstance()->findByName($this->_prepopName, $this->_toasterOptions['id'], Application_Model_Models_Container::TYPE_PREPOP);
        if($prepop) {
            $this->_prepopContent = $prepop->getContent();
            $this->_prepopContainerId = $prepop->getId();
        }

        // user role should be a member at least to be able to edit
        if(!Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_PAGE_PROTECTED)) {
            return '<span class="prepop-content" id="prepop-' . $this->_prepopName . '">' . $this->_prepopContent . '</span>';
        }

        //assign common view vars for the prepop
        $this->_view->prepopContent    = $this->_prepopContent;
        $this->_view->prepopConainerId = $this->_prepopContainerId;
        $this->_view->elementType      = $this->_options[0];

        $rendererName = '_renderPrepop' . ucfirst(array_shift($this->_options));
        if(method_exists($this, $rendererName)) {
            return $this->$rendererName();
        }
        throw new Exceptions_SeotoasterWidgetException($this->_translator->translate('Wrong prepop type <strong>' . $prepopType . '</strong>'));

    }

    protected function _renderPrepopTextarea() {
        if(!$this->_prepopContent && isset($this->_options[0])) {
            $this->_view->prepopContent = $this->_options[0];
        }
        $this->_view->onJsElementAction = 'blur';
        return $this->_view->render('element.prepop.phtml');
    }

    protected function _renderPrepopCheckbox() {
        $this->_view->onJsElementAction = 'click';
        $this->_view->options           = $this->_generateSelectOptions();
        return $this->_view->render('element.prepop.phtml');
    }

    protected function _renderPrepopSelect() {
        $this->_view->onJsElementAction = 'change';
        $this->_view->options           = $this->_generateSelectOptions();
        return $this->_view->render('element.prepop.phtml');
    }

    protected function _renderPrepopRadio() {
        $this->_view->onJsElementAction = 'click';
        $this->_view->options           = $this->_generateSelectOptions();
        return $this->_view->render('element.prepop.phtml');
    }

    protected function _renderPrepopText() {
        if(!$this->_prepopContent && isset($this->_options[0])) {
            $this->_view->prepopContent = $this->_options[0];
        }
        $this->_view->onJsElementAction = 'blur';
        return $this->_view->render('element.prepop.phtml');
    }

    private function _generateSelectOptions() {
        $arrayValues = array_map(function($value) {
            return trim($value);
        }, array_values($this->_options));

        return array_combine($arrayValues, array_map(function($option) {
            return ucfirst($option);
        }, $arrayValues));
    }

    protected function _getAllowedOptions() {

    }

}
