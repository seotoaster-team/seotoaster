<?php
/**
 * Header widget
 *
 * @author iamne
 */
class Widgets_Header_Header extends Widgets_AbstractContent {

	protected function  _init() {
		$this->_type    = (isset($this->_options[1]) && $this->_options[1] == 'static') ? Application_Model_Models_Container::TYPE_STATICHEADER : Application_Model_Models_Container::TYPE_REGULARHEADER;
		parent::_init();
	}

	protected function  _load() {
        $this->_container = $this->_find();
		$headerContent    = (null === $this->_container) ? '' : $this->_container->getContent();

        if(Tools_Security_Acl::isAllowed($this)) {
            if(empty($headerContent) && in_array(Widgets_Content_Content::DEFAULT_CONTENT, $this->_options) && $this->_type === Application_Model_Models_Container::TYPE_REGULARHEADER){
                $optionKey = array_search(Widgets_Content_Content::DEFAULT_CONTENT, $this->_options);
                if(isset($this->_options[$optionKey+1])){
                    $defaultText = filter_var($this->_options[$optionKey+1], FILTER_SANITIZE_STRING);
                }
                if(!empty($defaultText)){
                    $headerContent = $defaultText;
                }
            }


			$headerContent .= $this->_generateAdminControl(600, 140); //$this->_addAdminLink($this->_type, (!$headerContent) ? null : $header->getId(), 'Click to edit header', 604, 130);
			if ((bool)Zend_Controller_Action_HelperBroker::getExistingHelper('config')->getConfig('inlineEditor') && !in_array('readonly',$this->_options) && !in_array('href',$this->_options)){
				$headerContent = '<div class="container-wrapper">'.$headerContent.'</div>';
			}
		}

        if (in_array('href',$this->_options)){
            $headerContent = trim(rawurlencode($headerContent));
        }
		return $headerContent;
	}

	/**
	 * Overrides abstract class method
	 * For Header and Content widgets we have a different resource id
	 *
	 * @return string ACL Resource id
	 */
	public function  getResourceId() {
		return Tools_Security_Acl::RESOURCE_CONTENT;
	}
}

