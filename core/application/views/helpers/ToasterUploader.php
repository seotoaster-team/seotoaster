<?php
/**
 * ToasterUploader
 *
 * @author Pavel Kovalyov <pavlo.kovalyov@gmail.com>
 */
class Zend_View_Helper_ToasterUploader extends Zend_View_Helper_Abstract {

	private $_libraryPath = 'system/js/external/jquery/plugins/file-upload/';

	private $_uploadForm = null;
	private $_caller = null;
	private $_uploadActionUrl = array(
		'controller'=>'backend_upload',
		'action'=>'upload'
	);
    
	public function toasterUploader(){
		$this->_caller = array(
			'controller' => Zend_Controller_Front::getInstance()->getRequest()->getControllerName(),
			'action'	=> Zend_Controller_Front::getInstance()->getRequest()->getActionName()
		);

		$this->_uploadForm = new Application_Form_Upload();
		
		$this->_processForm(implode('#',$this->_caller));
		
		return $this->_uploadForm;
	}

	public function _processForm($caller) {
		//assign all necessary JS and CSS
		$this->view->headLink()->appendStylesheet($this->view->websiteUrl.$this->_libraryPath.'jquery.fileupload-ui.css');
		$this->view->headScript()->appendFile($this->view->websiteUrl.$this->_libraryPath.'jquery.fileupload.js');
		$this->view->headScript()->appendFile($this->view->websiteUrl.$this->_libraryPath.'jquery.fileupload-ui.js');
		
		$jsInitScript = $this->view->websiteUrl.$this->_libraryPath.'scripts/'.str_replace('#', '_', $caller).'.js';
		$this->view->headScript()->appendFile($jsInitScript);
		
		switch ($caller) {
			case 'backend_theme#themes':
				break;
			case 'backend_media#upload':
				break;
			default:
				break;
		}
		$this->_uploadForm->setAction($this->view->url($this->_uploadActionUrl));

		$this->_uploadForm->getElement('caller')->setValue($caller);
	}
}