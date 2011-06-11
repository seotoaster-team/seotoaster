<?php

/**
 * Content tools
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Tools_Content_Tools {

	const PATTERN_LINKWITHHREF     = '~<a.*href\s*="(.*)".*>.*</a>~Uu';

	const PATTERN_LINKWITHOUTTITLE = '~<a\s+[^\s]*\s*href="([^\s]*)"\s*>.*</a>~uU';

	public static function findLinksInContent($content, $hrefsOnly = false, $pattern = self::PATTERN_LINKWITHHREF) {
		$matches = array();
		preg_match_all($pattern, $content, $matches);
		return (!$hrefsOnly) ? $matches : (isset ($matches[1])) ? $matches[1] : array();
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

}