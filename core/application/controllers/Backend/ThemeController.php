<?php
/**
 * Controller for all stuff that belongs to theme, template, css.
 */

class Backend_ThemeController extends Zend_Controller_Action {

	private $_protectedTemplates = array('index', 'default', 'category', 'news');

	private $_websiteConfig = null;
	private $_themeConfig = null;

	public function  init() {
		parent::init();
		if(!Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_THEMES)) {
			$this->_redirect($this->_helper->website->getUrl(), array('exit' => true));
		}
		$this->view->websiteUrl = $this->_helper->website->getUrl();

		//$this->_helper->layout->disableLayout();

		$this->_websiteConfig	= Zend_Registry::get('website');
		$this->_themeConfig		= Zend_Registry::get('theme');

		$this->_translator = Zend_Registry::get('Zend_Translate');
	}

	/**
	 * Method returns template editing screen
	 * and saves edited template
	 */
	public function templateAction() {
		$templateForm = new Application_Form_Template();
		$templateName   = $this->getRequest()->getParam('id');
		$mapper = new Application_Model_Mappers_TemplateMapper();
		$currentTheme = $this->_helper->config->getConfig('current_theme');
		if(!$this->getRequest()->isPost()) {
			if($templateName) {
				$template = $mapper->find($templateName);
				if($template instanceof Application_Model_Models_Template) {
					$templateForm->getElement('content')->setValue($template->getContent());
					$templateForm->getElement('name')->setValue($template->getName());
					$templateForm->getElement('id')->setValue($template->getName());
					$templateForm->getElement('previewImage')->setValue($template->getPreviewImage());
				}
			}
			//building list of image folders
			$imageFolders = Tools_Filesystem_Tools::scanDirectoryForDirs($this->_websiteConfig['path'] . $this->_websiteConfig['images']);
			$this->view->imageFolders = $imageFolders;
		} else {
			if($templateForm->isValid($this->getRequest()->getPost())) {
				$templateData = $templateForm->getValues();
				$originalName = $templateData['id'];
				if (!empty($originalName)){
					$status = 'update';
					//find template by original name
					$template = $mapper->find($originalName);
					if (null === $template){
						$this->_helper->response->response($this->_translator->translate('Can\'t create template'), true);
					}
					// avoid renaming of system protected templates
					if (!in_array($template->getName(), $this->_protectedTemplates)) {
						$template->setName($templateData['name']);
					}
					$template->setContent($templateData['content'])
							 ->setPreviewImage($templateData['previewImage']);
				} else {
					$status = 'new';
					//if ID missing and name is not exists and name is not system protected - creating new template
					if ( (null!==$mapper->find($templateData['name'])) || in_array($templateData['name'], $this->_protectedTemplates) ) {
						$this->_helper->response->response($this->_translator->translate('Template exists'), true);
					}
					$template = new Application_Model_Models_Template($templateData);
				}

				// saving/updating template in db
				$result = $mapper->save($template);
				// saving to file in theme folder
				$currentThemePath = realpath($this->_websiteConfig['path'] . $this->_themeConfig['path'] . $currentTheme);
				$filepath = $currentThemePath.'/'.$templateData['name'].'.html';
				try {
					if ($filepath) {
						Tools_Filesystem_Tools::saveFile($filepath, $templateData['content']);
					}
				} catch (Exceptions_SeotoasterException $e) {
					error_log($e->getMessage());
				}

				$this->_helper->response->response($status, false);

			} else {
				$errorMessages = array();
				$validationErrors = $templateForm->getErrors();
				$messages = array(
					'name' => array(
						'isEmpty'	           => 'Template name field can\'t be empty.',
						'notAlnum'             => 'Template name contains characters which are non alphabetic and no digits',
						'stringLengthTooLong'  => 'Template name field is too long.',
						'stringLengthTooShort' => 'Template name field is too short.'),
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

				$this->_helper->response->response($errorMessages, true);
			}
		}
		$this->view->templateForm = $templateForm;
	}

	/**
	 * Method return form for editing css files for current theme
	 * and saves css file content
	 */
	public function editcssAction() {
		$cssFiles =	$this->_buildCssFileList();
		$defaultCss = $this->_websiteConfig['path'] . $this->_themeConfig['path'] . key(current($cssFiles));

		$editcssForm = new Application_Form_Css();
		$editcssForm->getElement('cssname')->setMultiOptions($cssFiles);

		//checking, if form was submited via POST then
		if ($this->getRequest()->isPost()){
			$postParams = $this->getRequest()->getParams();
			if (isset($postParams['getcss']) && !empty ($postParams['getcss'])) {
				$cssName = $postParams['getcss'];
				try {
					$content = Tools_Filesystem_Tools::getFile($this->_websiteConfig['path'] . $this->_themeConfig['path'] . $cssName);
					$this->_helper->response->response($content, false);
				} catch (Exceptions_SeotoasterException $e){
					$this->_helper->response->response($e->getMessage(), true);
				}
			} else {
				if (is_string($postParams['content']) && empty($postParams['content'])){
					$editcssForm->getElement('content')->setRequired(false);
				}
				if ($editcssForm->isValid($postParams)){
					$cssName = $postParams['cssname'];
					try {
						Tools_Filesystem_Tools::saveFile($this->_websiteConfig['path'] . $this->_themeConfig['path'] . $cssName, $postParams['content']);
						$params = array(
							'websiteUrl' => $this->_helper->website->getUrl(),
							'themePath'	 => $this->_websiteConfig['path'].$this->_themeConfig['path'],
							'currentTheme' =>$this->_helper->config->getConfig('current_theme')							 
						);
						$concatCss = Tools_Factory_WidgetFactory::createWidget('ConcatCss', array('refresh' => true), $params);
						$concatCss->render();
						$this->_helper->response->response($this->_translator->translate('CSS saved') , false);
					} catch (Exceptions_SeotoasterException $e) {
						$this->_helper->response->response($e->getMessage(), true);
					}
				}
			}
			$this->_helper->response->response($this->_translator->translate('Undefined error'), true);
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
		$currentThemeName	= $this->_helper->config->getConfig('current_theme');
		$currentThemePath	= realpath($this->_websiteConfig['path'] . $this->_themeConfig['path'] . $currentThemeName);

		$cssFiles = Tools_Filesystem_Tools::findFilesByExtension($currentThemePath, 'css', true);

		$cssTree = array();
		foreach ($cssFiles as $file){
			// don't show concat.css for editing
			if (strtolower(basename($file)) == Widgets_ConcatCss_ConcatCss::FILENAME) { 
				continue; 
			}
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
					$templates = $mapper->fetchAll();
					$templateList = array();
					foreach ($templates as $template) {
						array_push($templateList, array(
							'id'	=> $template->getId(),
							'name'	=> $template->getName(),
							'content' => $template->getContent(),
							'preview_image' => $template->getPreviewImage()
						));
					}
					$this->view->templates = $templateList;
					$this->view->protectedTemplates = $this->_protectedTemplates;
					echo $this->view->render($this->getViewScript('templateslist'));
					exit;
					break;
				default:
					$template = $mapper->find($listtemplates);
					if ($template instanceof Application_Model_Models_Template) {
						$template = array(
								'id'		=> $template->getId(),
								'name'		=> $template->getName(),
								'content'	=> $template->getContent(),
								'preview'	=> $template->getPreviewImage()
						);
						$this->_helper->response->response($template, true);
					} else {
						//$response = array('done'=> false);
						$this->_helper->response->response($this->_translator->translate('Template not found'), true);
					}
					break;
			}
		}
	}

	/**
	 * Method which delete template (AJAX)
	 */
	public function deletetemplateAction(){
		if ($this->getRequest()->isPost()){
			$mapper = new Application_Model_Mappers_TemplateMapper();
			$templateId = $this->getRequest()->getPost('id');
			if ($templateId){
				$template = $mapper->find($templateId);
				if ($template instanceof Application_Model_Models_Template && !in_array($template->getName(), $this->_protectedTemplates)){
					$result = $mapper->delete($template);
					if ($result) {
						$currentThemePath = realpath($this->_websiteConfig['path'] . $this->_themeConfig['path'] . $this->_helper->config->getConfig('current_theme'));
						$filename = $currentThemePath.'/'.$template->getName().'.html';
						Tools_Filesystem_Tools::deleteFile($filename);
						$status = $this->_translator->translate('Template deleted.');
					} else {
						$status = $this->_translator->translate('Can\'t delete template or template doesn\'t exists.');
					}
//					echo json_encode(array('done'=>true, 'status' => $status));
					$this->_helper->response->response($status, false);
//					exit;
				}
			}
//			echo json_encode(array('done'=>true, 'status' => $this->_translator->translate('Template doesn\'t exists')));
			$this->_helper->response->response($this->_translator->translate('Template doesn\'t exists'), false);
//			exit;
		}
	}

	public function themesAction(){
		$themePath = $this->_websiteConfig['path'] . $this->_themeConfig['path'];
		$themeDirs = Tools_Filesystem_Tools::scanDirectoryForDirs($themePath);
		$themesList = array();
		foreach ($themeDirs as $themeName) {
			$files = Tools_Filesystem_Tools::scanDirectory($themePath.$themeName);
			//check for necessary html files
			$requiredFiles = preg_grep('/^('.implode('|', $this->_protectedTemplates).')\.html$/i', $files);
			if (sizeof($requiredFiles) != 4){
				continue;
			}
			$previews = preg_grep('/^preview\.(png|jpg|gif)$/i', $files);
			array_push($themesList, array(
				'name' => $themeName,
				'preview' => !empty ($previews) ? $this->_helper->website->getUrl().$this->_themeConfig['path'].$themeName.'/'.reset($previews) : $this->_helper->website->getUrl().'system/images/no_image.png',
				'isCurrent' => ($this->_helper->config->getConfig('current_theme') == $themeName)
			));
		}

		$this->view->themesList = $themesList;
	}

	public function applythemeAction(){
		if ($this->getRequest()->isPost()){
			$selectedTheme = trim($this->getRequest()->getParam('themename'));
			if (is_dir($this->_websiteConfig['path'].$this->_themeConfig['path'].$selectedTheme)) {
				$errors = $this->_saveThemeInDatabase($selectedTheme);
				if (empty ($errors)){
					$status = sprintf($this->_translator->translate('The theme "%s" applied!'), $selectedTheme);
//					echo json_encode(array('done'=>true,'errors'=>$errors, 'status' => $status));
					$this->_helper->response->response($status, false);
				} else {
//					echo json_encode(array('done'=>true,'errors'=>$errors));
					$this->_helper->response->response($errors, true);
				}
//				exit;
			}
		}
		$this->_redirect($this->_helper->website->getUrl());
	}

	public function deletethemeAction(){
		if ($this->getRequest()->isPost()) {

			$themeName = $this->getRequest()->getParam('name');
			if ($this->_helper->config->getConfig('current_theme') == $themeName) {
//				echo json_encode(array('done'=>false, 'status'=>'trying to remove current theme'));
				$this->_helper->response->response($this->_translator->translate('trying to remove current theme'), true);
			}
			$status = Tools_Filesystem_Tools::deleteDir($this->_websiteConfig['path'].$this->_themeConfig['path'].$themeName);

//			echo json_encode(array('done'=>true,'status'=>$status));
//			exit;
			$this->_helper->response->response($status, false);
		}
		$this->_redirect($this->_helper->website->getUrl());
	}

	/**
	 * Method saves theme in database
	 */
	private function _saveThemeInDatabase($themeName){
		$errors = array();
		$themePath = $this->_websiteConfig['path'].$this->_themeConfig['path'].$themeName;
		$themeFiles = Tools_Filesystem_Tools::scanDirectory($themePath, true);
		$htmlFiles = array();
		$previewFiles = array();

		foreach ($themeFiles as $file) {
			if (preg_match('/^(.*)\.(html|htm)$/', $file)) {
                $htmlFiles[] = $file;
            }
		}
		if (is_dir($themePath.'/images/templatepreview/')){
			$previewFiles = Tools_Filesystem_Tools::scanDirectory($themePath.'/images/templatepreview/');
		}

		$necessaryTmpls =	preg_grep('/('.implode('|',$this->_protectedTemplates).')\.(html|htm)$/', $htmlFiles);
		if ( empty($htmlFiles) || sizeof($necessaryTmpls) < 4 ) {
			return array($this->_translator->translate('Can\'t apply this theme: some files are missing'));
		}

		$mapper = new Application_Model_Mappers_TemplateMapper();
		$removedTemplatesCount = $mapper->clearTemplates(); // this will remove all templates except system required. @see $_protectedTemplates

		$nameValidator = new Zend_Validate();
		$nameValidator->addValidator(new Zend_Validate_Alnum(true))
					  ->addValidator(new Zend_Validate_StringLength(array(3,45)));

		foreach ($htmlFiles as $file) {
			preg_match_all('/^(.*)\/(.*)\.(html|htm)$/', $file, $matches);
			$tmplName = $matches[2][0];

			if (!$nameValidator->isValid($tmplName)){
				array_push($errors, 'Not valid name for template: '.$tmplName);
				continue;
			}

			$template = $mapper->findByName($tmplName);
			if (! $template instanceof Application_Model_Models_Template) {
				$template = new Application_Model_Models_Template();
				$template->setName($tmplName);
			}

			// getting template content
			try{
				$content = Tools_Filesystem_Tools::getFile($file);
				$template->setContent($content);
			} catch (Exceptions_SeotoasterException $e){
				array_push($errors, 'Can\'t read template file: '.$tmplName);
			}

			// getting template preview image
			$previews = preg_grep('/('.$tmplName.')\.(png|gif|jpg)/', $previewFiles);
			if (!empty ($previews)){
				$previewImage = $this->_themeConfig['path'].$themeName.'/images/templatepreview/'.reset($previews);
			} else {
				$previewImage = '';
			}
			$template->setPreviewImage($previewImage);

			// saving template to db
			$mapper->save($template);
			unset($template);
		}

		//updating config table
		$configTable = new Application_Model_DbTable_Config();
		$updateConfig = $configTable->update(array('value' => $themeName), array('name = ?'=>'current_theme'));

		return $errors;
	}
}

