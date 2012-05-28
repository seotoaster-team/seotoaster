<?php
/**
 * Prepop
 * @author: iamne <eugene@seotoaster.com> Seotoaster core team
 * Date: 5/25/12
 * Time: 6:37 PM
 */
class Application_Form_Prepop extends Application_Form_Container {

    public function init() {
        if(!$this->_containerType) {
            $this->_containerType = Application_Model_Models_Container::TYPE_PREPOP;
        }

        $this->addElement('text', 'content', array(
            'id'       => 'content',
            'value'    => $this->_content,
            'filters'  => array('StringTrim')
        ));

        parent::init();
    }

}
