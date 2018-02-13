<?php

/**
 * Setting
 *
 * @author Pavel Kovalyov <pavlo.kovalyov@gmail.com>
 */
class Installer_Form_Settings extends Zend_Form {

	public function init() {
        $translator = $this->getTranslator();

		$this->setName(strtolower(__CLASS__))
			 ->setAction('')
			 ->setMethod(Zend_Form::METHOD_POST)
			 ->setDecorators(array(
				'FormElements',
				'Form'
				))
			 ->setElementDecorators(array(
				'ViewHelper',
				'Label',
				new Zend_Form_Decorator_HtmlTag(array('tag' => 'div', 'class' => array('mt10px') ))
				));

		$this->addElement('text', 'adminName', array(
			'label' => 'Admin name',
			'required' => true,
			'allowEmpty' => false
		));

		$this->addElement('text', 'adminEmail', array(
			'label'		=> 'Admin email',
			'required'	=> true,
			'allowEmpty'=> false,
			'validators'=> array(new Zend_Validate_EmailAddress(array(
				'domain'    => false,
				'allow'     => Zend_Validate_Hostname::ALLOW_DNS || Zend_Validate_Hostname::ALLOW_LOCAL
			)))
		));
		
		$this->addElement('password', 'adminPassword', array(
			'label'		=> 'Password',
			'renderPassword' => true,
			'required'	=> true,
			'allowEmpty'=> false,
			'validators'=> array(new Zend_Validate_StringLength(array('min'=>5))),
			'data-field' => 'pw'
		));

		$this->addElement('password', 'rePassword', array(
			'label'          => 'Verify password',
			'renderPassword' => 'true',
			'required'       => true,
			'allowEmpty'     => false,
			'validators'     => array(
				array('Identical', false, array('token' => 'adminPassword')),
				array('StringLength', false, array('min'=>5))
			),
			'data-field' => 'pw-confirm'
		));

		$this->addElement('checkbox', 'createAccount', array(
			'label'         => 'Yes, I want my FREE SEO Samba account, the statistics and the plugins. Email my account token to me.',
			'decorators'    => array(
				'Label',
				'ViewHelper'
			)
		));

		$this->addElement('text', 'sambaToken', array(
			'label'      => 'I already have my token, here it is.',
			'decorators' => array(
				array('Label', array('class' => 'green')),
				'ViewHelper'
			)
		));
		
		$this->addElement('hidden', 'check', array(
			'value'	=> 'settings',
			'ignore'=> true,
			'decorators'=> array('ViewHelper')
		));
		
		$this->addElement('submit', 'submit', array(
			'label'		=> 'Toast it!',
			'decorators'=> array('ViewHelper')
		));
		
		$this->setElementFilters(array(new Zend_Filter_StringTrim(), new Zend_Filter_StripTags()));
	}
	
	public function isValid($data){
		$valid = parent::isValid($data);

		foreach ($this->getElements() as $element) {
			if ($element->hasErrors()){
				$currentClass = $element->getAttrib('class');
				if (!empty($currentClass)){
					$element->setAttrib('class', $currentClass.' error');
				} else {
					$element->setAttrib('class', 'error');
				}
			}
		}

		return $valid;
	}
	
}