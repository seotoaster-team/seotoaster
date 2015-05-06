<?php

class Application_Form_Template extends Application_Form_Secure
{

    protected $_title = '';

    protected $_content = '';

    protected $_previewImage = '';

    protected $_templateId = '';

    protected $_themeName = '';

    protected $_type = '';

    protected $_shortcuts = '';

    public function init()
    {
        parent::init();
        $this->setMethod(Zend_Form::METHOD_POST)
            ->setAttrib('id', 'frm_template')
            ->setDecorators(array('ViewScript'))
            ->setElementDecorators(array('ViewHelper'));

        $this->addElement(
            'text',
            'name',
            array(
                'id'         => 'title',
                'label'      => 'Template name',
                'value'      => $this->_title,
                'required'   => true,
                'filters'    => array('StringTrim'),
                'class'      => array('templatename'),
                'decorators' => array('ViewHelper', 'Label'),
                'validators' => array(
                    array('stringLength', false, array(3, 45)),
                    new Zend_Validate_Regex(array('pattern' => '/^[\s\w-_]*$/u'))
                )
            )
        );

        $this->addElement(
            'textarea',
            'content',
            array(
                'id'         => 'template-content',
                'label'      => 'Paste your HTML code here:',
                'cols'       => '85',
                'rows'       => '33',
                'value'      => $this->_content,
                'required'   => true,
                'filters'    => array('StringTrim'),
                'decorators' => array('ViewHelper', 'Label')
            )
        );

        $this->addElement(
            new Zend_Form_Element_Select(array(
                'name'         => 'templateType',
                'id'           => 'template-type',
                'label'        => 'Used for',
                'multiOptions' => Application_Model_Mappers_TemplateMapper::getInstance()->fetchAllTypes(),
                'value'        => ($this->_type) ? $this->_type : Application_Model_Models_Template::TYPE_REGULAR
            ))
        );

        $this->addElement(
            new Zend_Form_Element_Select(array(
                'name'         => 'shortcuts',
                'id'           => 'useful-shortcuts',
                'label'        => 'Useful shortcuts',
                'class'        => 'grid_4 alpha',
                'multiOptions' => $this->_prepareShortcuts(),
                'value'        => ($this->_shortcuts) ? $this->_shortcuts : ''
            ))
        );

        $this->addElement(
            'hidden',
            'id',
            array(
                'value' => $this->_templateId,
                'id'    => 'template_id'
            )
        );

        $this->addElement(
            new Zend_Form_Element_Hidden(array(
                'id'   => 'pageId',
                'name' => 'pageId',
            ))
        );

        $this->addElement(
            new Zend_Form_Element_Button(array(
                'name'   => 'submit',
                'type'   => 'submit',
                'label'  => 'Save changes',
                'class'  => 'btn ticon-save formsubmit mt15px',
                'ignore' => true,
                'escape' => false
            ))
        );

        $this->setElementDecorators(array('ViewHelper', 'Label'));
        $this->getElement('shortcuts')->removeDecorator('Label');
        $this->getElement('submit')->removeDecorator('Label');
        $this->removeDecorator('DtDdWrapper');
        $this->removeDecorator('DlWrapper');
    }

    protected function _prepareShortcuts()
    {
        $widgetList = array();
        $allowedWidgets = Tools_Widgets_Tools::getAllowedOptions();
        foreach ($allowedWidgets as $allowedWidget) {
            foreach ($allowedWidget as $key => $options) {
                if(isset($options['group'])){
                    $widgetList[$options['group']]['{$' . $options['option'] . '}'] = $options['alias'];
                }else{
                    $widgetList['System Shortcuts']['{$' . $options['option'] . '}'] = $options['alias'];
                }

            }
        }
        return $widgetList;
    }
}

