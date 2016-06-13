<?php

class Widgets_Content_Content extends Widgets_AbstractContent {

    const POPUP_WIDTH  = 960;

    const POPUP_HEIGHT = 560;

    const TEXT_SEPARATOR = '#show-more#';

    protected function  _init() {
        $this->_type = (isset($this->_options[1]) && $this->_options[1] == 'static') ? Application_Model_Models_Container::TYPE_STATICCONTENT : Application_Model_Models_Container::TYPE_REGULARCONTENT;
        parent::_init();
    }

    protected function _load() {
        $this->_container = $this->_find();
        $isPublished      = $this->_checkPublished();
        if(end($this->_options) == 'ajax') {
            $this->_view             = new Zend_View(array('scriptPath' => dirname(__FILE__) . '/views'));
            $this->_view->websiteUrl = Zend_Controller_Action_HelperBroker::getStaticHelper('website')->getUrl();
            $this->_view->type       = $this->_type;
            $this->_view->name       = $this->_name;
            if($this->_pageId == null) {
                $page = Application_Model_Mappers_PageMapper::getInstance()->findByUrl($this->_toasterOptions['url']);
                $this->_pageId = $page->getId();
            }
            $this->_view->pageId      = $this->_pageId;
            $this->_view->isPublished = $isPublished;
            $this->_view->controls    = Tools_Security_Acl::isAllowed($this) ? $this->_generateAdminControl(self::POPUP_WIDTH, self::POPUP_HEIGHT): '';
            $params                   = Zend_Json::encode(Zend_Controller_Front::getInstance()->getRequest()->getParams());
            $this->_view->params      = $params;
            $this->_cacheId           = $this->_name .'_'. $this->_type .'_pid_'. $this->_pageId .'_'. Zend_Controller_Action_HelperBroker::getStaticHelper('Session')->getCurrentUser()->getRoleId() . substr(md5($params), 0, 27);

            return (!$isPublished && !Tools_Security_Acl::isAllowed($this)) ? '' : $this->_view->render('ajax.phtml');
        }

        $content = ($this->_container === null) ? '' : $this->_container->getContent();

        if(($this->_type === Application_Model_Models_Container::TYPE_REGULARCONTENT && in_array('show-more', $this->_options)) && (!empty($this->_container))){
            $len = strlen(self::TEXT_SEPARATOR);
            $textButton = 'show more...';
            $textButtonFloat = 'read less';
            $numbersSymbols = 0;
            $contentLen = strlen($content);
            $denyShowMore = false;
            $separatorExistence = strpos($content, self::TEXT_SEPARATOR);

            if(!empty($this->_options[array_search('show-more',$this->_options)+1])){
                $textButton = $this->_options[array_search('show-more',$this->_options)+1];
            }
            if(!empty($textButton)){
                if(!empty($this->_options[2])){
                    $buttonParams = explode('|',$this->_options[2]);
                    $count = count($buttonParams);
                    if($count > 1 && $count < 3){
                        $textButton = $buttonParams[0];
                        $textButtonFloat = $buttonParams[1];
                    }
                }
                $textButton = '<span class="show-more-widget-button-show"><a href="#" title="'.$textButton.'">'. $textButton . '</a></span>';
                $textButtonFloat = '<span class="show-more-widget-button-less"><a href="#" title="'. $textButtonFloat .'">'. $textButtonFloat . '</a></span>';

            }
            $denyTags = array_search('deny',$this->_options);
            $clearContent = '';
            if($denyTags){
                $clearContent = strip_tags($content);
                $contentLen = strlen($clearContent);
            }

            if(!empty($this->_options[array_search('show-more',$this->_options)+2])){
                $numbersSymbols = ((int)$this->_options[array_search('show-more',$this->_options)+2]) ? (int)$this->_options[array_search('show-more',$this->_options)+2] : 0;

            }
            $separatorExistenceClearContent = strpos($clearContent, self::TEXT_SEPARATOR) + $len;
            if(!empty($separatorExistence)){
                if(!empty($clearContent)){
                    if($separatorExistenceClearContent >= $contentLen){
                        $denyShowMore = true;
                        $clearContent = (str_replace(self::TEXT_SEPARATOR, ' ', $clearContent));
                    }
                    $content = $clearContent;
                }
                $contentOpenPart = substr($content, 0, strpos($content, self::TEXT_SEPARATOR));
                $contentClosePart = substr($content,strpos($content, self::TEXT_SEPARATOR) + $len);
            }else{
                if(!empty($clearContent)){
                    $content = $clearContent;
                }
                $contentOpenPart = substr($content, 0, $numbersSymbols);
                $contentClosePart = substr($content, $numbersSymbols);

                if($numbersSymbols > $contentLen){
                    $denyShowMore = true;
                }
            }

            $showMoreFirstPart = '<span class="show-more-widget-open">'.$contentOpenPart.'</span>';
            $showMoreLastPart = '<div class="show-more-content">'.$showMoreFirstPart.$textButton.'<span class="show-more-widget-close">'.$contentClosePart.' '.$textButtonFloat.'</span></div>';

            if(($separatorExistence || $numbersSymbols) && (!$denyShowMore)){
                $content = $showMoreLastPart;
            }
        }


        if (Tools_Security_Acl::isAllowed($this)) {
            $content .= $this->_generateAdminControl(self::POPUP_WIDTH, self::POPUP_HEIGHT);
            if ((bool)Zend_Controller_Action_HelperBroker::getStaticHelper('config')->getConfig('inlineEditor') && !in_array('readonly',$this->_options)){
                $content = '<div class="container-wrapper '. ($isPublished ? '' : 'unpublished') .'">' . $content . '</div>';
            }
            elseif(!$isPublished && !in_array('readonly',$this->_options)) {
                $content = '<div class="unpublished">' . $content . '</div>';
            }
        }
        else {
            $content = (!$isPublished) ? '' : $content;
        }

        return $content;
    }

    /**
     * Checks if content published
     *
     * @return bool true if published
     */
    private function _checkPublished() {
        if($this->_container === null) {
            return true;
        }

        if(!$this->_container->getPublished()) {
            if($this->_container->getPublishingDate()) {

                $zDate  = new Zend_Date();
                $result = $zDate->compare(strtotime($this->_container->getPublishingDate()));

                if($result == 0 || $result == 1) {
                    $this->_container->setPublishingDate('')
                        ->setPublished(true);
                    Application_Model_Mappers_ContainerMapper::getInstance()->save($this->_container);
                }
            }
        }

        return (bool) $this->_container->getPublished();
    }
    
	/**
	 * Overrides abstract class method
	 * For Header and Content widgets we have a different resource id
	 *
	 * @return string ACL Resource id
	 */
	public function  getResourceId() {
		return Tools_Security_Acl::RESOURCE_CONTENT;
	}
}

