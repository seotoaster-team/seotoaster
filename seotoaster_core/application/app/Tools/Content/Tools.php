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

	public static function proccessFormMessagesIntoHtml($messages, $formClassName = '') {
		$form = ($formClassName) ? new $formClassName() : null;
		$html = '<ul>';
		foreach ($messages as $element => $messageData) {
			$errMessages = array_values($messageData);
			$html .= '<li><strong>' . (($form) ? $form->getElement($element)->getLabel() : $element) . '</strong>';
			$html .= '<ul>';
			foreach ($errMessages as $message) {
				$html .= '<li>' . $message . '</li>';
			}
			$html .= '</ul>';
			$html .= '</li>';
		}
		$html .= '</ul>';
		unset($form);
		return $html;
	}

	public static function applyDeeplink(Application_Model_Models_Deeplink $deeplink, $content) {
		$websiteHelper  = Zend_Controller_Action_HelperBroker::getStaticHelper('Website');
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

		$pattern = '~([\>]{1}|\s+|[\/\>]{1})(' . $deeplink->getName() . ')([\<]{1}|\s+|[.,!\?]+)~sUui';
		if(preg_match($pattern, $content, $matches)) {
			//$url = '<a ' . (($deeplink->getType() == Application_Model_Models_Deeplink::TYPE_EXTERNAL) ? 'target="_blank"' : '') . 'href="' . (($deeplink->getType() == Application_Model_Models_Deeplink::TYPE_INTERNAL) ? $websiteHelper->getUrl() . $deeplink->getUrl() : $deeplink->getUrl()) . '">' . $deeplink->getName() . '</a>';
			$url = '<a ' . (($deeplink->getType() == Application_Model_Models_Deeplink::TYPE_EXTERNAL) ? 'target="_blank"' : '') . 'href="' . (($deeplink->getType() == Application_Model_Models_Deeplink::TYPE_INTERNAL) ? $websiteHelper->getUrl() . $deeplink->getUrl() : $deeplink->getUrl()) . '">' . $matches[2] . '</a>';
			return self::insertReplaced(preg_replace($pattern, '$1' . $url . '$3', $content, 1));
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
}