<?php
class Application_Form_Repeat extends Zend_Form
{
    private   $_name = 'repeatForm';

    protected $_quantity;
    protected $_replace;
    protected $_orderContent;

    public function getQuantity() {
        return $this->_quantity;
    }

    public function setQuantity($_quantity)
    {
        $this->_quantity = $_quantity;
        $this->getElement('quantity')->setValue($this->_quantity);
    }

    public function getOrderContent() {
        return $this->_orderContent;
    }

    public function setOrderContent($_orderContent)
    {
        $this->_orderContent = $_orderContent;
        $this->getElement('orderContent')->setValue($this->_orderContent);
    }

    public function init()
    {
        $this->setName($this->_name)
            ->setMethod(Zend_FORM::METHOD_POST)
            ->setDecorators(array(
                'FormElements',
                'Form'
            ))
            ->setElementDecorators(array(
                'ViewHelper',
                'Label'
            ))
            ->setElementFilters(array('StringTrim', 'StripTags'));

        $this->addElement('text', 'quantity', array(
            'value'       => $this->_quantity,
            'validators'  => array(new Zend_Validate_Int()),
            'placeholder' => '0'
        ));

        $this->addElement('text', 'orderContent', array(
            'value'       => $this->_orderContent,
            'validators'  => array(new Zend_Validate_StringLength()),
            'placeholder' => '2,5,1...'
        ));

        $this->addElement(new Zend_Form_Element_Button(array(
            'name'  => 'submit',
            'type'  => 'submit',
            'label' => 'Save',
            'class' => 'btn ticon-save mr-grid',
            'ignore' => true,
            'escape'=> false
        )));
    }
}
