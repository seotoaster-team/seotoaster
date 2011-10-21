<?php

/**
 * ToasterDraforlive
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Zend_View_Helper_ToasterDraftorlive extends Zend_View_Helper_Abstract {

	/**
	 * Default text 'published' checkbox label
	 *
	 */
	const DEFAULT_LABEL_PUBLISHED = 'Published';

	/**
	 * Default datepicker label
	 *
	 */
	const DEFAULT_LABEL_PUBLISHAT = 'Publish automatically on';

	/**
	 * Default datepicker format (Oct 21, 2011)
	 *
	 */
	const DEFAULT_DATE_FORMAT     = 'M j, Y';

	/**
	 * Draft or live view helper.
	 *
	 * @param array $options
	 * @return string
	 */
	public function toasterDraftorlive($options = array()) {
		if(empty($options)) {
			throw new Exceptions_SeotoasterException('Options required for this helper!');
		}
		if(isset($options['onselectCallback']) && $options['onselectCallback']) {
			$this->view->onselectCallback = $options['onselectCallback'];
		}
		$this->view->publishedLabel = (isset($options['publishedLabel']) && $options['publishedLabel']) ? $options['publishedLabel'] : self::DEFAULT_LABEL_PUBLISHED;
		$this->view->publishAtLabel = (isset($options['publishAtLabel']) && $options['publishAtLabel']) ? $options['publishAtLabel'] : self::DEFAULT_LABEL_PUBLISHAT;
		$this->view->dateFormat     = (isset($options['dateFormat']) && $options['dateFormat']) ? $options['dateFormat'] : self::DEFAULT_DATE_FORMAT;
		$this->view->published      = (isset($options['published'])) ? $options['published'] : true;
		$this->view->publishAt      = (isset($options['publishAt']) && $options['publishAt']) ? $options['publishAt'] : '';
		return $this->view->render('admin' . DIRECTORY_SEPARATOR . '_draftorlive.phtml');
	}

}

