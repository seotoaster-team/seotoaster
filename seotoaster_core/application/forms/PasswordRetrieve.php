<?php
/**
 * User: iamne Eugene I. Nezhuta <eugene@seotoaster.com>
 * Date: 4/17/12
 * Time: 3:53 PM
 */

class Application_Form_PasswordRetrieve extends Zend_Form {

	public function init() {
        $translator = Zend_Registry::get('Zend_Translate');

		$this->setAttribs(array(
			'id'     => 'password-retrive',
//			'class'  => 'grid_12 mt20px mb20px',
			'method' => Zend_Form::METHOD_POST
		));

		$this->setDecorators(array('FormElements', 'Form'));

		$this->addElement(new Zend_Form_Element_Text(array(
			'name'       => 'email',
			'id'         => 'retrieveEmail',
			'label'      => 'E-mail',
            'aria-label' => 'E-mail',
			'allowEmpty' => false,
			'filters'    => array(
				new Zend_Filter_StringTrim()
			),
			'validators' => array(
				new Zend_Validate_NotEmpty(),
				new Tools_System_CustomEmailValidator(),
				new Zend_Validate_Db_RecordExists(array(
					'table' => 'user',
					'field' => 'email'
				))
			),
		)));


        $this->setElementDecorators(array(
            'ViewHelper',
            'Label',
            array('HtmlTag', array('tag' => 'p'))
        ));

        $this->addElement(new Zend_Form_Element_Button(array(
            'name'   => 'retrieve',
            'ignore' => true,
            'label'  => $translator->translate('Retrieve'),
            'aria-label' => 'Retrieve',
            'type'   => 'submit',
            'decorators'=> array('ViewHelper')
        )));

        $this->removeDecorator('DtDdWrapper');
        $this->removeDecorator('DlWrapper');
	}

}
