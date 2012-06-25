<?php

/**
 * NewsController
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class NewsController extends Zend_Controller_Action {

    public function indexAction() {
		if(in_array($this->getRequest()->getParam('page'), $this->_helper->page->getCanonicMap())) {
			$this->_forward('list');
		}
		else {
			$this->_forward('index', 'index');
		}
    }

    public function listAction() {
		$mapper        = Application_Model_Mappers_TemplateMapper::getInstance();
		$newsTempate   = $mapper->find('news');
		$newsIndexPage = new Application_Model_Models_Page();
		$newsIndexPage->setHeaderTitle($this->_helper->language->translate('Newsroom'));
	    $newsIndexPage->setH1($this->_helper->language->translate('Newsroom'));
	    $newsIndexPage->setNavName($this->_helper->language->translate('Newsroom'));
	    //$newsIndexPage->
	    $newsIndexPage->setTemplateId($newsTempate->getName());
		$themeData     = Zend_Registry::get('theme');
		$parserOptions = array(
			'websiteUrl'     => $this->_helper->website->getUrl(),
			'websitePath'    => $this->_helper->website->getPath(),
			'currentTheme'   => $this->_helper->config->getConfig('currentTheme'),
			'themePath'      => $themeData['path']
		);
		$parser = new Tools_Content_Parser($newsTempate->getContent(), $newsIndexPage->toArray(), $parserOptions);
		$this->_complete($parser->parse(), $newsIndexPage->toArray(), true);
	}

	private function _complete($pageContent, $pageData, $newsPage = false) {
        $head    = '';
        $body    = '';

        $themeData = Zend_Registry::get('theme');
        $parserOptions = array(
            'websiteUrl'   => $this->_helper->website->getUrl(),
            'websitePath'  => $this->_helper->website->getPath(),
            'currentTheme' => $this->_helper->config->getConfig('currentTheme'),
            'themePath'    => $themeData['path'],
        );

        //parsing seo data
        $seoData = Tools_Seo_Tools::loadSeodata();
        $seoData = $seoData->toArray();
        unset($seoData['id']);
        $seoData = array_map(function($item) use ($pageData, $parserOptions){
            $parser = new Tools_Content_Parser(null, $pageData, $parserOptions);
            return !empty($item) ? $parser->setContent($item)->parseSimple() : $item;
        }, $seoData);

        preg_match('~(<body[^\>]*>)(.*)</body>~usi', $pageContent, $body);

        $this->_extendHead($pageContent);

        $this->view->placeholder('seo')->exchangeArray($seoData);
        $this->view->websiteUrl      = $this->_helper->website->getUrl();
        $this->view->websiteMainPage = $this->_helper->website->getDefaultPage();
        $this->view->currentTheme    = $this->_helper->config->getConfig('currentTheme');
        $this->view->newsPage        = $newsPage;
        if(Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_ADMINPANEL)) {
            unset($pageData['content']);
            $this->view->pageData = $pageData;
            $body[1] .= $this->_helper->admin->renderAdminPanel($this->_helper->session->getCurrentUser()->getRoleId());
        }
        $this->view->pageData = $pageData;
        $this->view->bodyTag  = $body[1];
        $this->view->content  = $body[2];
        $this->view->minify   = Zend_Controller_Action_HelperBroker::getExistingHelper('config')->getConfig('enableMinify')
            && !Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_LAYOUT);
	}

    private function _extendHead($pageContent) {
        preg_match('~<head>.*</head>~sUui', $pageContent, $head);

        if (empty($head)){
            return;
        }

        $dom = new DOMDocument();
        @$dom->loadHTML($head[0]);

        foreach ($dom->getElementsByTagName('head') as $head){
            foreach ($head->childNodes as $node ) {
                $name = preg_replace('~[^\w\d]*~','',$node->nodeName);
                switch ($name) {
                    case 'meta':
                        $attributes = array();
                        foreach($node->attributes as $attr){
                            $attributes[$attr->name] = $attr->value;
                            unset($attr);
                        }
                        if (isset($attributes['name'])){
                            $this->view->headMeta()->appendName($attributes['name'], $attributes['content']);
                        } elseif (isset($attributes['http-equiv'])){
                            $this->view->headMeta()->appendHttpEquiv($attributes['http-equiv'], $attributes['content']);
                        } else {
                            if ($this->view->doctype()->isRdfa() && isset($attributes['property'])){
                                $this->view->headMeta()->setProperty($attributes['property'], $attributes['content']);
                            } elseif ($this->view->doctype()->isHtml5() && isset($attributes['charset'])){
                                $this->view->headMeta()->setCharset($attributes['charset']);
                            } else {
                                $this->view->placeholder('misc')->set($this->view->placeholder('misc').PHP_EOL.$dom->saveXML($node));
                            }
                        }
                        unset($attributes);
                        break;
                    case 'title':
                        $this->view->headTitle($node->nodeValue);
                        break;
                    case 'script':
                        $attributes = array();
                        foreach($node->attributes as $attr){
                            $attributes[$attr->name] = $attr->value;
                            unset($attr);
                        }
                        if (isset($attributes['type'])){
                            $type = $attributes['type'];
                            unset($attributes['type']);
                        } else {
                            $type = 'text/javascript';
                        }
                        if ($node->hasAttribute('src')){
                            $this->view->headScript()->appendFile($node->getAttribute('src'), $type, $attributes);
                        } else {
                            if ($type !== 'text/javascript'){
                                $this->view->placeholder('misc')->set($this->view->placeholder('misc').PHP_EOL.$dom->saveXML($node));
                            } else {
                                $this->view->headScript()->appendScript($node->nodeValue, $type, $attributes);
                            }
                        }
                        break;
                    case 'link':
                        if (strtolower($node->getAttribute('rel')) === 'stylesheet' ){
                            $this->view->headLink()->appendStylesheet(
                                $node->getAttribute('href'),
                                $node->getAttribute('media')
                            );
                            break;
                        }
                    default:
                        $this->view->placeholder('misc')->set($this->view->placeholder('misc').PHP_EOL.$dom->saveXML($node));
                        break;
                }
            }
        }
        return;
    }
}

