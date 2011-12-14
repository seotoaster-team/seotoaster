<?php

/**
 * Signup
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Application_Form_Signup extends Application_Form_User {

	public function init() {
		parent::init();

		$this->setAttribs(array(
			'id' => 'signup-form'
		));

		$this->removeElement('roleId');

		$saveButton = $this->getElement('saveUser');
		$this->removeElement('saveUser');

		$this->addElement(new Zend_Form_Element_Captcha('verification', array(
			'label'   => "Please verify you're a human",
			'captcha' => array(
				'captcha'        => 'Image',
				'font'           => 'system/fonts/Alcohole.ttf',
				'imgDir'         => 'tmp',
				'imgUrl'         => 'tmp',
				'dotNoiseLevel'  => 0,
				'lineNoiseLevel' => 0,
				'wordLen'        => 5,
				'timeout'        => 300,
			),
		)));

		$this->addElement(($saveButton->setLabel('Sign Up')));

		$this->removeDecorator('DtDdWrapper');
		$this->removeDecorator('DlWrapper');
	}
}

