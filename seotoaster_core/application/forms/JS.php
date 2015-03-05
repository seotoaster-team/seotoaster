<?php
/**
 * Css
 *
 * @author Pavel Kovalyov <pavlo.kovalyov@gmail.com>
 */
class Application_Form_JS extends Application_Form_Secure {
	protected $_content = '';
	protected $_jsList = '';

	public function init() {
        parent::init();
        $this->setMethod(Zend_Form::METHOD_POST);
		$this->setAttrib('id', 'editjsform');

		$this->addElement('select', 'jsname', array(
			'required'	=> true,
			'id'		=> 'jslist',
			'class'		=> 'w30'
		));
        $this->getElement('jsname')->setDisableTranslator(true);

		$this->addElement('select', 'jsminification', array(
			'id'		=> 'jsminification',
			'class'		=> 'w30',
            'multiOptions'     => array(
                '' => 'Select minimization type',
                'minify' => 'Minimization',
                'combine' => 'Combine'
            )
		));

		$this->addElement('checkbox', 'jsminify', array(
			'id'		=> 'jsminify',
            'label'     => 'Compress only'
 		));

		$this->addElement('checkbox', 'jscombine', array(
			'id'		=> 'jscombine',
            'label'     => 'Combine and compress all js files'
		));

		$this->addElement('textarea', 'content', array(
			'id'		 => 'jscontent',
			'required'	 => true,
			'allowEmpty' => true,
			'spellcheck' => 'false',
			'value'		 => $this->_content
		));

		$this->addElement(new Zend_Form_Element_Button(array(
			'type'   => 'submit',
			'name'   => 'submit',
			'label'  => 'Save JS',
			'class'  => 'formsubmit btn ticon-save',
			'ignore' => true,
			'escape' => false
		)));

		$this->setDecorators(array('ViewScript'))
			->setElementDecorators(array('ViewHelper'))
			->setElementFilters(array('StringTrim'));
	}
}