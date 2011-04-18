<?php
/**
 * @todo: use response helper for json responses
 */

class Backend_ThemeController extends Zend_Controller_Action {

	private $_protectedTemplates = array('index', 'default', 'category', 'news');

	public function  init() {
		parent::init();
		if(!Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_THEMES)) {
			$this->_redirect($this->_helper->website->getUrl(), array('exit' => true));
		}
		$this->view->websiteUrl = $this->_helper->website->getUrl();
		$this->_helper->layout->disableLayout();
	}

	/**
	 * Method returns template editing screen
	 * and saves edited template
	 */
	public function templateAction() {
		$templateForm = new Application_Form_Template();
		$templateId   = $this->getRequest()->getParam('id');
		$mapper = new Application_Model_Mappers_TemplateMapper();
		$currentTheme = $this->_helper->config->getConfig('current_theme');
		if(!$this->getRequest()->isPost()) {
			if($templateId) {
				$template = $mapper->find($templateId);
				if($template instanceof Application_Model_Models_Template) {
					$templateForm->getElement('content')->setValue($template->getContent());
					$templateForm->getElement('name')->setValue($template->getName());
					$templateForm->getElement('id')->setValue($template->getId());
					$templateForm->getElement('previewImage')->setValue($template->getPreviewImage());
				}
			}
		} else {
			if($templateForm->isValid($this->getRequest()->getPost())) {			
				$templateData = $templateForm->getValues();
				if (!empty($templateData['id'])){
					//if ID set - find template by id
					$template = $mapper->find($templateData['id']);
					if (null === $template){
						echo json_encode(array('done'=>false, 'errors'=>array('Can\'t create template') ));
						exit;
					}
					// avoid renaming of system protected templates
					if (!in_array($template->getName(), $this->_protectedTemplates)) {
						$template->setName($templateData['name']);
					}
					$template->setContent($templateData['content']);
					$template->setPreviewImage($templateData['previewImage']);
				} else {
					//if ID missing and name is not exists and name is not system protected - creating new template
					if ( $mapper->findByName($templateData['name']) || in_array($templateData['name'], $this->_protectedTemplates) ) {
						echo json_encode(array('done'=>false, 'errors'=>array('Template exists') ));
						exit;
					}
					$template = new Application_Model_Models_Template($templateData);
				}
				
				// saving/updating template in db
				$result = $mapper->save($template);
				// saving to file in theme folder
				$websiteConfig	= Zend_Registry::get('website');
				$themeConfig	= Zend_Registry::get('theme');
				$currentThemePath = realpath($websiteConfig['path'] . $themeConfig['path'] . $currentTheme);
				$filepath = $currentThemePath.'/'.$templateData['name'].'.html';
				try {
					if ($filepath) {
						Tools_Filesystem_Tools::saveFile($filepath, $templateData['content']);
					}
				} catch (Exceptions_SeotoasterException $e) {
					error_log($e->getMessage());
				}
				echo(json_encode(array('done'=>true, 'status' => $result)));
				exit;
			} else {
				$errorMessages = array();
				$validationErrors = $templateForm->getErrors();
				$messages = array(
					'name' => array(
						'isEmpty'	=> 'Template name field can\'t be empty.',
						'notAlnum' => 'Template name contains characters which are non alphabetic and no digits',
						'stringLengthTooLong'	=> 'Template name field is too long.',
						'stringLengthTooShort'	=> 'Template name field is too short.'),
					'content' => array(
						'isEmpty'	=> 'Content can\'t be empty.'
					)
				);
				foreach ($validationErrors as $element => $errors){
					if (empty ($errors)) {
						continue;
					}
					foreach ($messages[$element] as $n=>$message){
						if (in_array($n, $errors)){
							array_push($errorMessages, $message);
						}
					}
				}
				echo json_encode( array('done'=>false, 'errors' => $errorMessages ) );
				exit;
			}
		}
		$this->view->templateForm = $templateForm;
	}

	/**
	 * Method return form for editing css files for current theme
	 * and saves css file content
	 */
	public function editcssAction() {
		$websiteConfig	= Zend_Registry::get('website');
		$themeConfig	= Zend_Registry::get('theme');

		$cssFiles =	$this->_buildCssFileList();
		$defaultCss = $websiteConfig['path'] . $themeConfig['path'] . key(current($cssFiles));

		$editcssForm = new Application_Form_Css();
		$editcssForm->getElement('cssname')->setMultiOptions($cssFiles);
		
		//checking, if form was submited via POST then
		if ($this->getRequest()->isPost()){
			$postParams = $this->getRequest()->getParams();
			if (!isset($postParams['getcss']) && $editcssForm->isValid($postParams)){
				$cssName = $postParams['cssname'];
				try {
					Tools_Filesystem_Tools::saveFile($websiteConfig['path'] . $themeConfig['path'] . $cssName, $postParams['content']);
					// @todo: concat.css goes here
					echo json_encode(array('done' => true));
				} catch (Exceptions_SeotoasterException $e) {
					echo json_encode(array('done' => false, 'error' => $e->getMessage()));
				}
				exit;
			} elseif (isset($postParams['getcss']) && !empty ($postParams['getcss'])) {
				$cssName = $postParams['getcss'];
				try {
					$content = Tools_Filesystem_Tools::getFile($websiteConfig['path'] . $themeConfig['path'] . $cssName);
					echo json_encode(array('done' => true, 'content' => $content));
				} catch (Exceptions_SeotoasterException $e){
					echo json_encode(array('done' => false, 'error' => $e->getMessage()));
				}
				exit;
			}
		} else {
			try {
				$editcssForm->getElement('content')->setValue(Tools_Filesystem_Tools::getFile($defaultCss));
			} catch (Exceptions_SeotoasterException $e){
				$this->view->errorMessage = $e->getMessage();
			}
		}
		
		$this->view->editcssForm = $editcssForm;
	}

	/**
	 * Method build a list of css files for current theme
	 * with subdirectories 
	 * @return <type>
	 */
	private function _buildCssFileList() {
		$websiteConfig	= Zend_Registry::get('website');
		$themeConfig	= Zend_Registry::get('theme');
		$currentThemeName	= $this->_helper->config->getConfig('current_theme');
		$currentThemePath	= realpath($websiteConfig['path'] . $themeConfig['path'] . $currentThemeName);

		$cssFiles = Tools_Filesystem_Tools::findFilesByExtension($currentThemePath, 'css', true);

		$cssTree = array();
		foreach ($cssFiles as $file){
			preg_match_all('~^'.$currentThemePath.'/([a-zA-Z0-9-_\s/.]+/)*([a-zA-Z0-9-_\s.]+\.css)$~i', $file, $sequences);
			$subfolders = $currentThemeName.'/'.$sequences[1][0];
			$files = array();
			foreach ($sequences[2] as $key => $value) {
				$files[$subfolders.$value] = $value;
			}
			
			if (!array_key_exists($subfolders, $cssTree)){
				$cssTree[$subfolders] = array();
			} 
			$cssTree[$subfolders] = array_merge($cssTree[$subfolders], $files);
			
		}

		return $cssTree;
	}


	/**
	 * Method returns list of templates or template content if id given in params (AJAX)
	 * @return html || json
	 */
	public function gettemplateAction(){
		if ($this->getRequest()->isPost()){
			$mapper = new Application_Model_Mappers_TemplateMapper();
			$listtemplates = $this->getRequest()->getParam('listtemplates');
			switch ($listtemplates) {
				case 'all':
					$templates = $mapper->fetchAll()->toArray();
					$this->view->templates = $templates;
					$this->view->protectedTemplates = $this->_protectedTemplates;
					echo $this->view->render($this->getViewScript('templateslist'));
					exit;
					break;
				default:
					$template = $mapper->find($listtemplates);
					if ($template instanceof Application_Model_Models_Template) {
						$response = array(
							'done' => true,
							'template' => array(
								'id'		=> $template->getId(),
								'name'		=> $template->getName(),
								'content'	=> $template->getContent(),
								'preview'	=> $template->getPreviewImage()
							)
						);
					} else {
						$response = array('done'=> false);
					}
					echo json_encode( $response );
					exit;
					break;
			}
		}
	}

	/**
	 * Method which delete template (AJAX)
	 */
	public function deletetemplateAction(){
		if ($this->getRequest()->isPost() && $this->getRequest()->isXmlHttpRequest()){
			$mapper = new Application_Model_Mappers_TemplateMapper();
			$templateId = $this->getRequest()->getPost('id');
			if ($templateId){
				$template = $mapper->find($templateId);
				if ($template instanceof Application_Model_Models_Template && !in_array($template->getName(), $this->_protectedTemplates)){
					$result = $mapper->delete($template);
					if ($result) {
						$websiteConfig	= Zend_Registry::get('website');
						$themeConfig	= Zend_Registry::get('theme');
						$currentThemePath = realpath($websiteConfig['path'] . $themeConfig['path'] . $this->_helper->config->getConfig('current_theme'));
						$filename = $currentThemePath.'/'.$template->getName().'.html';
						Tools_Filesystem_Tools::deleteFile($filename);
					}
					echo json_encode(array('done'=>true, 'status' => $result));
					exit;
				}
			}
			echo json_encode(array('done'=>true));
			exit;
		}
	}

	public function uploadthemeAction(){

	}

	public function themesAction(){
		
	}

	public function applythemeAction(){
		if ($this->getRequest()->isPost() && $this->getRequest()->isXmlHttpRequest()){
			$selectedTheme = $this->getRequest()->getParam('select_name');

		}
		$this->_redirect($this->_helper->website->getUrl());
	}

	public function deletethemeAction(){
		
	}
}

