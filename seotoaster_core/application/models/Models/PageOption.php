<?php
/**
 *
 */
class Application_Model_Models_PageOption extends Application_Model_Models_Abstract {

    protected $_title   = '';

    protected $_context = '';

    protected $_active  = true;

    public function setId($id) {
        $this->_id = $id;
        return $this;
    }

    public function setActive($active) {
        $this->_active = $active;
        return $this;
    }

    public function getActive() {
        return $this->_active;
    }

    public function setContext($context) {
        $this->_context = $context;
        return $this;
    }

    public function getContext() {
        return $this->_context;
    }

    public function setTitle($title) {
        $this->_title = $title;
        return $this;
    }

    public function getTitle() {
        return $this->_title;
    }
}
