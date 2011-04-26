<?php
/**
 * Upload
 *
 * @author Pavel Kovalyov <pavlo.kovalyov@gmail.com>
 */
class Application_Form_Upload extends Zend_Form {

	public function init(){

		$fileUpload = new Zend_Form_Element_File('file', array(
			'label' => 'Browse for themes',
			'decorators' => array('Label', 'File'),
			'multiple' => true
		));

		$this->addElement($fileUpload);

		$this->addElement('hidden', 'caller', array(
			'decorators' => array('ViewHelper')
		));

		$this->addElement('submit', 'submit', array(
			'label' => 'Upload',
			'decorators' => array('ViewHelper')
			));
		
		$this->setName('upload-form')
			 ->setMethod(self::METHOD_POST)
			 ->setAttrib('id', 'toaster-uploader')
			 ->setAttrib('enctype', self::ENCTYPE_MULTIPART)
			 ->setDecorators(array('FormElements', 'Form'));
	}
}