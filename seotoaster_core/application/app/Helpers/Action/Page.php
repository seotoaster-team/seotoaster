<?php

/**
 * Page helper
 *
 * Takes care of 404 page, 301 redirects, page url validation
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Helpers_Action_Page extends Zend_Controller_Action_Helper_Abstract {

	private $_cache      = null;

	private $_redirector = null;

	private $_website    = null;

	private $_canonicMap = array('index.html', 'index.htm');

	public function init() {
		$this->_cache      = Zend_Controller_Action_HelperBroker::getStaticHelper('Cache');
		$this->_redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('Redirector');
		$this->_website    = Zend_Controller_Action_HelperBroker::getStaticHelper('Website');
	}

	public function validate($pageUrl) {
		$pageUrl = (!$pageUrl) ? $this->_website->getDefaultPage() : preg_replace('/\.htm$/ui', '.html', $pageUrl);
		return $pageUrl;
	}

    /**
     * Filter url given to the toaster
     *
     * @param string $pageUrl
     * @return mixed|string
     */
    public function filterUrl($pageUrl) {
        $filterChain = new Zend_Filter();

        $filterChain->addFilter(new Zend_Filter_PregReplace(array('match' => '/-/', 'replace' => ' ')))
            ->addFilter(new Zend_Filter_Alnum(true))
            ->addFilter(new Zend_Filter_StringTrim())
            ->addFilter(new Zend_Filter_StringToLower('UTF-8'))
            ->addFilter(new Zend_Filter_PregReplace(array('match' => '/\s+/', 'replace' => '-')));

        // filtering the page url
        $pageUrl = $filterChain->filter($this->clean($pageUrl));

        // add .html if needed
		// TODO: in fact it always needed because of Zend_Filter_Alnum in $filterChain
	    if(!preg_match('/\.html$/', $pageUrl)) {
			$pageUrl .= '.html';
		}
		return $pageUrl;
	}

	public function doCanonicalRedirect($pageUrl) {
		$this->_redirector->setCode(301);
              	
        if(Tools_System_Tools::getUrlHost($_SERVER['HTTP_HOST']) != Tools_System_Tools::getUrlHost($this->_website->getUrl())) {
			$this->_redirector->gotoUrl($this->_website->getUrl() . $pageUrl);
		}

		if(in_array($pageUrl, $this->_canonicMap)) {
			$this->_redirector->gotoUrl($this->_website->getUrl());
		}
	}

    /**
     * Do a 301 redirect
     *
     * @param $pageUrl string
     */
    public function do301Redirect($pageUrl) {
		$redirectMap = $this->_cache->load('toaster_301redirects', '301redirects');
        if (!is_array($redirectMap)) {
            $redirectMap = array();
        }
		$this->_redirector->setCode(301);

		if($redirectMap === null || !isset($redirectMap[$this->_website->getUrl() . $pageUrl])) {
            if(!in_array($this->_website->getUrl() . $pageUrl, $redirectMap)) {
                $redirect = Application_Model_Mappers_RedirectMapper::getInstance()->fetchRedirectMap($pageUrl);
                if($redirect !== null) {
                    $redirectMap[$redirect->getDomainFrom() . $redirect->getFromUrl()] = $redirect->getdomainTo() . $redirect->getToUrl();
                    $this->_cache->save('toaster_301redirects', $redirectMap, '301redirects', array(), Helpers_Action_Cache::CACHE_NORMAL);
                }
            }
		}

        if(!empty($redirectMap)) {
		    $pageUrl = $this->_website->getUrl() . $pageUrl;
            if(isset($redirectMap[$pageUrl]) && $redirectMap[$pageUrl]) {
                $this->_redirector->gotoUrl($redirectMap[$pageUrl]);
    		}
        }
	}

	public function clean($pageUrl) {
        $filter = new Zend_Filter_PregReplace(array('match' => '/\.html$/', 'replace' => ''));
		return $filter->filter($pageUrl);
	}

	public function getCanonicMap() {
		return $this->_canonicMap;
	}

}

