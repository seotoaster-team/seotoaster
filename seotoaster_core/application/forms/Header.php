<?php

class Application_Form_Header extends Application_Form_Container
{

    public function init()
    {

        $this->addElement(
            'text',
            'content',
            array(
                'id'      => 'content',
                'value'   => $this->_content,
                'filters' => array('StringTrim'),
                'class'   => 'header-content'
            )
        );

        $this->addElement(
            'button',
            'submit',
            array(
                'type'   => 'submit',
                'id'     => 'btn-submit',
                'label'  => 'Save content',
                'class'  => 'formsubmit mt15px',
                'ignore' => true
            )
        );

        parent::init();

        if (!$this->_containerType) {
            $this->_containerType = Application_Model_Models_Container::TYPE_REGULARHEADER;
        }

        $classes = $this->getAttrib('class');
        $classes .= ' _fajax _reload';
        $this->setAttrib('class', $classes);
    }
}
