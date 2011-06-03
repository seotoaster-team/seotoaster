<?php
/**
 * Upload
 *
 * @author Pavel Kovalyov <pavlo.kovalyov@gmail.com>
 */
class Application_Form_Upload extends Zend_Form {

	public function init(){

		$this->setName('uploadForm')
			 ->setMethod(self::METHOD_POST)
			 ->setAttrib('id', 'toaster-uploader')
			 ->setDecorators(array(
				array('ViewScript', array('viewScript' => 'admin/uploadForm.phtml'))
			));
		
		$fileUpload = new Zend_Form_Element_File('file', array(
			'label' => 'Upload',
			'decorators' => array('File'),
			'multiple' => true
		));

		$this->addElement($fileUpload);

		$this->addElement('hidden', 'caller', array(
			'decorators' => array('ViewHelper')
		));

		$this->addElement('button', 'submit', array(
			'label' => 'Upload',
			'decorators' => array('ViewHelper')
		));
				
	}
}