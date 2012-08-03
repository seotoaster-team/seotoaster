<?php

/**
 * Rss pull the rss feed using url
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Widgets_Rss_Rss extends Widgets_Abstract {

	const RSS_DESC_LENGTH   = '250';

	const RSS_RESULT_COUNT   = '5';

	protected function  _init() {
		parent::_init();
		$this->_view = new Zend_View(array(
			'scriptPath' => dirname(__FILE__) . '/views'
		));
		$website = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
		$this->_view->websiteUrl = $website->getUrl();
	}

	protected function _load() {
		if(!isset($this->_options[0])) {
			throw new Exceptions_SeotoasterWidgetException('Rss feed url should be specified');
		}
		$scheme    = 'http';
		$feeds     = array();

		//little hack for options, needs to be somthing better
		if(preg_match('~^https?$~', $this->_options[0])) {
			$scheme            = array_shift($this->_options);
			$this->_options[0] = ltrim($this->_options[0], '/');
		}

		$feedUrl   = $scheme . '://' . str_replace(' ', '+', html_entity_decode($this->_options[0]));
		$maxResult = isset($this->_options[1]) ? $this->_options[1] : self::RSS_RESULT_COUNT;

		Zend_Uri::setConfig(array('allow_unwise' => true));

		if(!Zend_Uri::check($feedUrl)) {
			throw new Exceptions_SeotoasterWidgetException('Rss feed url is not valid.');
		}

		Zend_Feed_Reader::setCache(Zend_Registry::get('cache'));
		Zend_Feed_Reader::useHttpConditionalGet();
		try {
			$rss = Zend_Feed_Reader::import($feedUrl);
		}
		catch(Exception $e) {
			return $e->getMessage();
		}
		$i   = 0;
		foreach ($rss as $item) {
			if($i == $maxResult) {
				break;
			}
			$feeds[] = array(
				'title'       => $item->getTitle(),
				'link'        => $item->getLink(),
				'description' => Tools_Text_Tools::cutText(strip_tags($item->getDescription()), (isset($this->_options[2])? $this->_options[2] : self::RSS_DESC_LENGTH)),
				'pubDate'     => $item->getDateCreated(),
				'content'     => $item->getContent(),
				'authors'     => $item->getAuthors(),
				'image'       => $this->_getRssEntryImage($item->getContent())
			);
			$i++;
		}
		$this->_view->useImage = (isset($this->_options[3]) && $this->_options[3] == 'img') ? true : false;
		$this->_view->feed     = $feeds;
		return $this->_view->render('rss.phtml');
	}

	public static function getWidgetMakerContent() {
		$translator = Zend_Registry::get('Zend_Translate');
		$view       = new Zend_View(array(
			'scriptPath' => dirname(__FILE__) . '/views'
		));
		$websiteHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('website');
		$data = array(
			'title'   => $translator->translate('Rss feed'),
			'content' => $view->render('wmcontent.phtml'),
			'icons'   => array(
				$websiteHelper->getUrl() . 'system/images/widgets/rss.png',
			)
		);

		unset($view);
		return $data;
	}


	private function _getRssEntryImage($content) {
		preg_match('~<img\s+.*src=".+".*\s*/>~uUi', $content, $matches);
		return (!empty ($matches)) ? $matches[0] : '';
	}

}
