<?php

class Widgets_Content_Content extends Widgets_AbstractContent {

    protected function  _init() {
        $this->_type    = (isset($this->_options[1]) && $this->_options[1] == 'static') ? Application_Model_Models_Container::TYPE_STATICCONTENT : Application_Model_Models_Container::TYPE_REGULARCONTENT;
        parent::_init();
    }

    protected function _load() {
        $this->_container = $this->_find();
        $content          = ($this->_container === null) ? '' : $this->_container->getContent();

        if(Tools_Security_Acl::isAllowed($this)) {

            $content .= $this->_addAdminLink($this->_type, ($this->_container === null) ? null : $this->_container->getId(), 'Click to edit content', 964, 594);
            if ((bool)Zend_Controller_Action_HelperBroker::getStaticHelper('config')->getConfig('inlineEditor')){
                $content = '<div class="container-wrapper '. ($this->_checkPublished() ? '' : 'unpublished') .'">' . $content . '</div>';
            } elseif(!$this->_checkPublished()) {
                $content = '<div class="unpublished">' . $content . '</div>';
            }

        }

        return $content;
    }

    /**
     * Checks if content published
     *
     * @return bool true if published
     */
    private function _checkPublished() {
        if($this->_container === null) {
            return true;
        }

        if(!$this->_container->getPublished()) {
            if($this->_container->getPublishingDate()) {

                $zDate  = new Zend_Date();
                $result = $zDate->compare(strtotime($this->_container->getPublishingDate()));

                if($result == 0 || $result == 1) {
                    $this->_container->setPublishingDate('')
                        ->setPublished(true);
                    Application_Model_Mappers_ContainerMapper::getInstance()->save($this->_container);
                }
            }
        }

        return $this->_container->getPublished();
    }


    /*protected function  _load() {
        $this->_content  = Application_Model_Mappers_ContainerMapper::getInstance()->findByName($this->_name, $this->_pageId, $this->_type);
        $contentContent  = (null === $this->_content) ? '' : $this->_content->getContent();
        if(Tools_Security_Acl::isAllowed($this)) {
            //$contentContent = ($this->_checkPublished()) ? $contentContent : '<div style="border: 1px dashed red">' . $contentContent . '</div>';
            $contentContent .= $this->_addAdminLink($this->_type, ($this->_content === null) ? null : $this->_content->getId(), 'Click to edit content', 964, 594);
            if ((bool)Zend_Controller_Action_HelperBroker::getExistingHelper('config')->getConfig('inlineEditor')){
                $contentContent = '<div class="container-wrapper '. ($this->_checkPublished() ? '' : 'unpublished') .'">'.$contentContent.'</div>';
            } elseif(!$this->_checkPublished()) {
                $contentContent = '<div class="unpublished">'.$contentContent.'</div>';
            }
        }
        else {
            $contentContent = ($this->_checkPublished()) ? $contentContent : '';
        }
        return $contentContent;
    }*/

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