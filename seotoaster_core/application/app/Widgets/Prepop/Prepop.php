<?php
/**
 * Prepop widget
 *
 * @author: iamne <eugene@seotoaster.com> Seotoaster core team
 * Date: 5/24/12
 * Time: 3:58 PM
 */
class Widgets_Prepop_Prepop extends Widgets_AbstractContent {

    const TYPE_TEXT         = 'text';

    const TYPE_TEXTAREA     = 'textarea';

    const TYPE_CHECKBOX     = 'checkbox';

    const TYPE_SELECT       = 'select';

    const TYPE_RADIO        = 'radio';

    const OPTIONS_SEPARATOR = ',';

    const OPTIONS_SEPARATOR_OLD = '|';

    const OPTION_LINKS      = 'links';

    protected $_prepopName        = '';

    protected $_prepopContent     = null;

    protected $_prepopContainerId = null;

    protected $_cacheable         = false;

    protected function _init() {
        $this->_readonly = false;
        if (end($this->_options) == self::OPTION_READONLY) {
            $this->_readonly = true;
            unset($this->_options[array_search(self::OPTION_READONLY, $this->_options)]);
        }

        if (in_array('static', $this->_options)) {
            $this->_type = Application_Model_Models_Container::TYPE_PREPOPSTATIC;
            unset($this->_options[array_search('static', $this->_options)]);
        }
        else {
            $this->_type = Application_Model_Models_Container::TYPE_PREPOP;
        }

        parent::_init();
        $this->_prepopName          = array_shift($this->_options);
        $this->_view                = new Zend_View(array('scriptPath' => __DIR__ . '/views'));

        $this->_view->setHelperPath(APPLICATION_PATH . '/views/helpers/');
        $this->_view->addHelperPath('ZendX/JQuery/View/Helper/', 'ZendX_JQuery_View_Helper');

        $this->_view->prepopName    = $this->_prepopName;
        $this->_view->websiteUrl    = Zend_Controller_Action_HelperBroker::getStaticHelper('website')->getUrl();
        $this->_view->commonOptions = array(
            'pageId'        => $this->_pageId,
            'containerType' => $this->_type,
            'containerName' => $this->_name
        );
    }

    protected function  _load() {
        if(!isset($this->_options[0])) {
            throw new Exceptions_SeotoasterWidgetException('Not enough parameters for the widget <strong>prepop</strong>.');
        }

        $prepop = Application_Model_Mappers_ContainerMapper::getInstance()->findByName($this->_name, $this->_pageId, $this->_type);
        if($prepop) {
            $this->_prepopContent = $prepop->getContent();
            $this->_prepopContainerId = $prepop->getId();
        }
        // User role should be a member or not only for reading at least to be able to edit
        if (!Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_CONTENT) || $this->_readonly) {
            if($this->_options[0] == self::TYPE_CHECKBOX) {
                $translator           = $this->_translator;
                $this->_prepopContent = implode('&nbsp;', array_map(function($option) use($translator) {

                    return $translator->translate(ucfirst($option));
                }, array_filter(explode('~', $this->_prepopContent))));
            }
            if(array_search(self::OPTION_LINKS, $this->_options)){
                $this->_view->prepopName    = $this->_prepopName;
                $this->_view->prepopContent = $this->_prepopContent;
                return $this->_view->render('prepopLink.phtml');
            }
            elseif ($this->_readonly) {
                return $this->_prepopContent;
            }
            else {
                return '<span class="prepop-content" id="prepop-' . $this->_prepopName . '">' . $this->_prepopContent . '</span>';
            }
        }

        if(array_search(self::OPTION_LINKS, $this->_options)){
            $optionKey = array_search(self::OPTION_LINKS, $this->_options);
            $this->_options[$optionKey] = '';
        }
        //assign common view vars for the prepop
        $this->_view->prepopContent    = $this->_prepopContent;
        $this->_view->prepopConainerId = $this->_prepopContainerId;
        $this->_view->elementType      = $this->_options[0];

        $rendererName = '_renderPrepop' . ucfirst(array_shift($this->_options));
        $secureToken = Tools_System_Tools::initSecureToken(Tools_System_Tools::ACTION_PREFIX_CONTAINERS);
        $this->_view->secureToken = $secureToken;
        if(method_exists($this, $rendererName)) {
            return $this->$rendererName();
        }
        throw new Exceptions_SeotoasterWidgetException($this->_translator->translate('Wrong prepop type'));

    }

    protected function _renderPrepopTextarea() {
        if(!$this->_prepopContent && isset($this->_options[0])) {
            $this->_view->prepopContent = $this->_options[0];
        }
        $this->_view->limit             = isset($this->_options[1]) ? $this->_options[1] : 0;
        $this->_view->onJsElementAction = 'blur';
        return $this->_view->render('element.prepop.phtml');
    }

    protected function _renderPrepopCheckbox() {
        $this->_view->onJsElementAction = 'click';
        $options = $this->_generateSelectOptions();
        $values  = (is_array($options)) ? array_values($options) : array();
        if(empty($values) || sizeof($values) == 1 && !(boolean)$values[0]) {
            $options = array('yes' => '');
        }
        $this->_view->options = $options;
        return $this->_view->render('element.prepop.phtml');
    }

    protected function _renderPrepopSelect() {
        $this->_view->onJsElementAction = 'change';
        $options              = $this->_generateSelectOptions();
        $options[0]           = '-- ' . $this->_translator->translate('select one') . ' --';
        asort($options);
        $this->_view->options = $options;
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
        $this->_view->limit             = isset($this->_options[1]) ? $this->_options[1] : 0;
        $this->_view->onJsElementAction = 'blur';
        return $this->_view->render('element.prepop.phtml');
    }

    private function _generateSelectOptions() {
        if(!empty($this->_options[0]) && strpos($this->_options[0], '|') !== FALSE){
            $selectOptions = explode(self::OPTIONS_SEPARATOR_OLD, array_shift($this->_options));
        }else{
            $selectOptions = explode(self::OPTIONS_SEPARATOR, array_shift($this->_options));
        }
        $arrayValues   = (!is_array($selectOptions)) ? array() : array_map(function($value) {
            return trim($value);
        }, array_values($selectOptions));
        if(empty($arrayValues)) {
            return $arrayValues;
        }
        return array_combine($arrayValues, array_map(function($option) {
            return !intval($option) ? ucfirst($option) : $option;
        }, $arrayValues));
    }

    protected function _getAllowedOptions() {

    }

    public function  getResourceId() {
        return Tools_Security_Acl::RESOURCE_CONTENT;
    }
}
