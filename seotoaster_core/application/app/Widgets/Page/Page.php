<?php
/**
 * $page widget
 *
 * @author iamne
 */
class Widgets_Page_Page extends Widgets_Abstract {

    private $_aliases = array(
        'title' => 'headerTitle',
        'teaser' => 'teaserText',
        'navname' => 'navName'
    );

	protected function _init() {
		array_push($this->_cacheTags , 'pageid_'.$this->_toasterOptions['id']);
	}

	protected function  _load() {
        $option = $this->_validateOption($this->_options[0]);
	    if(isset($this->_toasterOptions[$option])) {
            $original = (isset($this->_options[1]) && $this->_options[1] == 'original');
            if($original) {
                $page = Application_Model_Mappers_PageMapper::getInstance()->find($this->_toasterOptions['id'], $original);
                $optionMakerName = 'get' . ucfirst($option);
                return $page->$optionMakerName();
            }
            return $this->_toasterOptions[$option];
        } else {
            $optionMakerName = '_generate' . ucfirst($this->_options[0]) . 'Option';
            if(method_exists($this, $optionMakerName)) {
                return $this->$optionMakerName();
            }
        }
		return 'Wrong widget option: <strong>' . $this->_options[0] . '</strong>';
	}

    private function _validateOption($option) {
        return array_key_exists($option, $this->_aliases) ? $this->_aliases[$option] : $option;
    }


	private function _generateIdOption() {
		return $this->_toasterOptions['id'];
	}


	private function _generateTeaserOption() {
		return $this->_toasterOptions['teaserText'];
	}

    private function _generateCategoryOption() {
        if(isset($this->_options[1])) {
            $content = '';
            switch($this->_options[1]) {
                case 'name':
                    $pageMapper = Application_Model_Mappers_PageMapper::getInstance();
                    $page       = $pageMapper->find($this->_toasterOptions['id']);
                    if(!$page instanceof Application_Model_Models_Page) {
                        throw new Exceptions_SeotoasterWidgetException('Cant load page!');
                    }
                    if($page->getParentId() > 0) {
                        $page = $pageMapper->find($page->getParentId());
                        $content = $page->getNavName();
                    }
                break;
                default:
                break;
            }
            return $content;
        }
        return '';

    }

	private function _generatePreviewOption() {
		$websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('Website');
		$pageHelper    = Zend_Controller_Action_HelperBroker::getStaticHelper('Page');
 		$files         = Tools_Filesystem_Tools::findFilesByExtension($websiteHelper->getPath() . $websiteHelper->getPreview(), '(jpg|gif|png|jpeg)', false, false, false);
		$pagePreviews  = array_values(preg_grep('~^' . $pageHelper->clean(preg_replace('~/+~', '-', $this->_toasterOptions['url'])) . '\.(png|jpg|gif|jpeg)$~', $files));

		if(!empty ($pagePreviews)) {
			//return '<a href="' . $websiteHelper->getUrl() . $this->_toasterOptions['url'] . '" title="' . $this->_toasterOptions['h1'] . '"><img src="' . $websiteHelper->getUrl() . $websiteHelper->getPreview() . $pagePreviews[0] . '" alt="'  . $pageHelper->clean($this->_toasterOptions['url']) . '" /></a>';
			return '<img class="page-teaser-image" src="' . $websiteHelper->getUrl() . $websiteHelper->getPreview() . $pagePreviews[0] . '" alt="'  . $pageHelper->clean($this->_toasterOptions['url']) . '" />';
		}
		return;
	}

	public static function getAllowedOptions() {
		$translator = Zend_Registry::get('Zend_Translate');
		return array(
			array(
				'alias'   => $translator->translate('Current page id'),
				'option' => 'page:id'
			),
            array(
                'alias'   => $translator->translate('Current page url'),
                'option' => 'page:url'
            ),
			array(
				'alias'   => $translator->translate('Current page h1'),
				'option' => 'page:h1'
			),
			array(
				'alias'   => $translator->translate('Current page title'),
				'option' => 'page:title'
			),
			array(
				'alias'   => $translator->translate('Current page teaser image'),
				'option' => 'page:preview'
			),
			array(
				'alias'   => $translator->translate('Current page teaser text'),
				'option' => 'page:teaser'
			),
            array(
                'alias'   => $translator->translate('Current page navigation name'),
                'option' => 'page:navname'
            )
		);
	}

}

