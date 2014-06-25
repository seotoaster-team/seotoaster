<?php

/**
 * Content tools
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Tools_Content_Tools {

	const PATTERN_LINKWITHHREF     = '~<a.*href\s*="(.*)".*>.*</a>~Uu';

	const PATTERN_LINKSIMPLE       = '~<a.*>.*</a>~suU';

	const PATTERN_HEADER           = '~<h[1-9]{1}.*>.*</h[1-9]{1}>~suU';

	const PATTERN_WIDGETS          = '~{\$.*}~sUu';

	const PATTERN_LINKWITHOUTTITLE = '~<a\s+[^\s]*\s*href="([^\s]*)"\s*>.*</a>~uU';

	public static function findLinksInContent($content, $hrefsOnly = false, $pattern = self::PATTERN_LINKWITHHREF) {
		$matches = self::_findInContent($content, $pattern);
		return (!$hrefsOnly) ? $matches : (isset ($matches[1]) ? $matches[1] : array());
	}

	public static function findHeadersInContent($content, $pattern = self::PATTERN_HEADER) {
		return self::_findInContent($content, $pattern);
	}

	public static function findWidgetsInContent($content, $pattern = self::PATTERN_WIDGETS) {
		return self::_findInContent($content, $pattern);
	}

	private static function _findInContent($content, $pattern) {
		$matches = array();
		preg_match_all($pattern, $content, $matches);
		return $matches;
	}

    public static function proccessFormMessages($messages){
        foreach ($messages as $elName => $message) {
            $messg = '';
            foreach ($message as $msg) {
                $messg .= $msg.' ';
            }
            $messageResult[$elName] = trim($messg);
        }
        return $messageResult;
    }


	public static function proccessFormMessagesIntoHtml($messages, $formClassName = '') {
        $translator       = Zend_Registry::get('Zend_Translate');
		$form = ($formClassName) ? new $formClassName() : null;
		$html = '<ul class="form-errors list-unstyled">';
		foreach ($messages as $element => $messageData) {
			$errMessages = array_values($messageData);
			$html .= '<li><span class="error-title">' . (($form) ? $form->getElement($element)->getLabel() : $element) . '</span>';
			$html .= '<ul class="list-unstyled text-italic">';
			foreach ($errMessages as $message) {
				$html .= '<li>' . $translator->translate($message) . '</li>';
			}
			$html .= '</ul>';
			$html .= '</li>';
		}
		$html .= '</ul>';
		unset($form);
		return $html;
	}

	public static function applyDeeplinkPerPage(Application_Model_Models_Deeplink $deeplink, Application_Model_Models_Page $page) {
		$containerMapper = Application_Model_Mappers_ContainerMapper::getInstance();
		//$containers      = $containerMapper->findByPageId($page->getId());
		$containers      = $containerMapper->findContentContainersByPageId($page->getId());
		if(!empty ($containers)) {
			foreach ($containers as $container) {
				$initialContentLength = strlen($container->getContent());

				if(Zend_Registry::isRegistered('applied') && Zend_Registry::get('applied') === true) {
					Zend_Registry::set('applied', false);
					return;
				}

				$container->setContent(self::applyDeeplink($deeplink, $container->getContent()));
				$container->registerObserver(new Tools_Seo_Watchdog(array(
					'unwatch' => '_updateDeeplinks'
				)));

				$gc = new Tools_Content_GarbageCollector(array('model' => $container));
				$gc->updateContentLinksRelatios();
				
				if($initialContentLength != strlen($container->getContent())) {
					$containerMapper->save($container);
					$container->notifyObservers();
				}
            }
		}
	}

	public static function applyDeeplink(Application_Model_Models_Deeplink $deeplink, $content) {

		$replaced = (Zend_Registry::isRegistered('replaced')) ? Zend_Registry::get('replaced') : array();
		if(!empty ($replaced)) {
			$replaced = array_map(function($item) {
				return strip_tags($item);
			}, $replaced);
			if(in_array($deeplink->getName(), $replaced)) {
				return $content;
			}
		}

        $websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('Website');
        $pattern = '~<a.*href="'.$websiteHelper->getUrl().$deeplink->getUrl().'".*>'.$deeplink->getName().'</a>~';
		if (preg_match($pattern, $content)) {
            return $content;
        }

		$linksMatches   = self::findLinksInContent($content, false, self::PATTERN_LINKSIMPLE);
		$headersMatches = self::findHeadersInContent($content);
		$widgetsMatches = self::findWidgetsInContent($content);

		if(!empty ($linksMatches) && isset($linksMatches[0])) {
			$content = self::extractWithReplace($linksMatches[0], $content);
		}
		if(!empty ($headersMatches) && isset($headersMatches[0])) {
			$content = self::extractWithReplace($headersMatches[0], $content);
		}
		if(!empty ($widgetsMatches) && isset($widgetsMatches[0])) {
			$content = self::extractWithReplace($widgetsMatches[0], $content);
		}

		$pattern = '~([\>]{1}|\s+|[\/\>]{1}|^)(' . $deeplink->getName() . ')([\<]{1}|\s+|[.,!\?]+|$)~uUi';
		if (preg_match($pattern, $content, $matches)) {
			Zend_Registry::set('applied', true);
			$url = '<a ' . (($deeplink->getType() == Application_Model_Models_Deeplink::TYPE_EXTERNAL) ? ('target="_blank" title="' . $deeplink->getUrl() . '" ') : '') . 'href="' . (($deeplink->getType() == Application_Model_Models_Deeplink::TYPE_INTERNAL) ? $websiteHelper->getUrl() . $deeplink->getUrl() : $deeplink->getUrl()) . '">' . $matches[2] . '</a>';
			$c = preg_replace('~' . $matches[2] . '~uU', $url, $content, 1);
			//return self::insertReplaced(preg_replace('~' . $matches[2] . '~uU', $url, $content, 1));
			return self::insertReplaced($c);
		}
		return self::insertReplaced($content);
	}

	public static function extractWithReplace($subject, $content, $sub = '#replcaewith') {
		if(!is_array($subject)) {
			throw new Exceptions_SeotoasterException('Array expected, ' . gettype($subject) . ' given.');
		}
		$replaced = (Zend_Registry::isRegistered('replaced')) ? Zend_Registry::get('replaced') : array();
		foreach ($subject as $item) {
			if(preg_match('~'. preg_quote($item) .'~Uiu', $content)) {
				$marker            = uniqid($sub);
				$replaced[$marker] = $item;
				$content           = str_replace($item, $marker, $content);
				Zend_Registry::set('replaced', $replaced);
			}
		}
		return $content;
	}


	public static function insertReplaced($content) {
		$replaced = (Zend_Registry::isRegistered('replaced')) ? Zend_Registry::get('replaced') : array();
		if(!empty($replaced)) {
			foreach ($replaced as $sub => $value) {
				$content = str_replace($sub, $value, $content);
			}
		}
		return $content;
	}

	public static function getMediaServer(){
        $mediaServers = self::getMediaServers();
        return (!empty($mediaServers)) ? $mediaServers[array_rand($mediaServers)] : '';
    }

	public static function getMediaServers($string = false) {
		$websiteData  = Zend_Registry::get('website');
		$mediaServers = (isset($websiteData['mediaServers']) && is_array($websiteData['mediaServers'])) ? $websiteData['mediaServers'] : array();
		return ($string) ? json_encode($mediaServers) : $mediaServers;
	}

	public static function applyMediaServers($string) {
		$configHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('config');
		$websiteData  = Zend_Registry::get('website');
		$domain       = str_replace('www.', '', $websiteData['url']);
		if($configHelper->getConfig('mediaServers')) {
			$mediaServer = self::getMediaServer();
			if($mediaServer) {
				return str_replace($websiteData['url'], $mediaServer . '.' . $domain, $string);
			}
		}
		return $string;
	}

    /**
     * Cut out edit links from contents.
     */
    public static function stripEditLinks($string) {
        if (Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_CONTENT)) {
            return preg_replace('/<a.*class=\"tpopup generator-links\".*>.*<\/a>/', '', $string);
        }
        return $string;
    }
}
