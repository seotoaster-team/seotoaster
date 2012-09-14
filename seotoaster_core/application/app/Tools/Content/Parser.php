<?php

/**
 * Seotoaster 2 parser
 *
 * @author Seotoaster Dev Team (SDT)
 */
class Tools_Content_Parser {

	const PARSE_DEEP = 5;

	private $_pageData  = null;

	private $_content   = null;

	private $_options   = array();

	private $_iteration = 0;

	public function  __construct($content = null, $pageData = null, $options = null) {
		if(null !== $content) {
			$this->_content = $content;
		}
		if(null !== $pageData) {
			$this->_pageData = $pageData;
		}
		if(null !== $options) {
			if(!is_array($options)) {
				throw new Exceptions_SeotoasterException('Parser options should be an array');
			}
			$this->_options = $options;
		}
	}

	public function parse() {
		$this->_parse();
		$this->_changeMedia();
		$this->_runMagicSpaces();
		return $this->_content;
	}

	public function getPageData() {
		return $this->_pageData;
	}

	public function setPageData(array $pageData) {
		$this->_pageData = $pageData;
		return $this;
	}

	public function getContent() {
		return $this->_content;
	}

	public function setContent($content) {
		if(empty($content)) {
			throw new Exceptions_SeotoasterException('Content passed to the parse is empty');
		}
		$this->_content = $content;
		return $this;
	}

	public function getOptions() {
		return $this->_options;
	}

	public function setOptions(array $options) {
		$this->_options = $options;
		return $this;
	}

	/**
	 * Simple parsing of content. No theme and media changes, no magic space proccessing
	 *
	 * @return null
	 */
	public function parseSimple() {
		$this->_parse();
		return $this->_content;
	}

	private function _parse() {
		$this->_iteration++;
		$replacement = '';
		$widgets = $this->_findWidgets();

		if(empty($widgets) || ($this->_iteration >= self::PARSE_DEEP)) {
			return;
		}
		foreach ($widgets as $widgetData) {
			try {
				$widget = Tools_Factory_WidgetFactory::createWidget($widgetData['name'], $widgetData['options'], array_merge($this->_pageData, $this->_options));
				$replacement = (is_object($widget)) ? $widget->render() : $widget;
			}
			catch (Exceptions_SeotoasterException $se) {
				$replacement = $se->getMessage() . ' Can not load widget: <b>' . $widgetData['name'] . '</b>';
			}
			$this->_replace($replacement, $widgetData['name'], $widgetData['options']);
		}
		$this->_parse();
	}

	private function _changeMedia() {
		$webPathToTheme = $this->_options['websiteUrl'] . $this->_options['themePath'] . $this->_options['currentTheme'];
		$this->_content = preg_replace('~["\']+/*images/(.*)["\']~Usui', $webPathToTheme . '/images/$1', $this->_content);
		$this->_content = preg_replace('~(<link.*?href=")(\/{0,1}[A-Za-z0-9.\/_\-]+\.css[a-z0-9=\?]*)(".*?>)~Uu','$1' . $webPathToTheme . '/$2' . '$3' , $this->_content);
		$this->_content = preg_replace('~(<script.*?src=")(\/{0,1}[A-Za-z0-9-\/_.\-]+\.js)(".*?>)~ui', '$1' . $webPathToTheme . '/$2' . '$3' , $this->_content);

		$favicoPath = $this->_options['themePath'] . $this->_options['currentTheme'] . '/favicon.ico';
		if(file_exists($favicoPath)) {
			$this->_content = preg_replace('~href="\/favicon.ico"~Uus', 'href="' . $webPathToTheme . '/favicon.ico"', $this->_content);
		}
	}

	private function _findWidgets() {
		$widgets = array();
		//[\w\s\-:,\p{L}\p{M}\p{P}\?\&=~+@#\&\/\>\<]
		preg_match_all('/{\$([\w]+:*[^{}]*)}/ui', $this->_content, $found);
		if(!empty ($found) && isset($found[1])) {
			foreach($found[1] as $widgetString) {
				$expWidgetString = explode(':', $widgetString);
				$widgetName = array_shift($expWidgetString);
				$widgetOptions = ($expWidgetString) ? $expWidgetString : array();
				$widgets[] = array(
					'name'    => $widgetName,
					'options' => $widgetOptions
				);
			}
		}
		return $widgets;
	}

	private function _runMagicSpaces() {
		preg_match_all('~{([\w]+)}~ui', $this->_content, $spacesFound);
		if(!empty($spacesFound) && isset($spacesFound[1])) {
			foreach($spacesFound[1] as $spaceName) {
				try {
					$magicSpace     = Tools_Factory_MagicSpaceFactory::createMagicSpace($spaceName, $this->_content, array_merge($this->_pageData, $this->_options));
					$this->_content = $magicSpace->run();
				}
				catch (Exception $e) {
					error_log($e->getMessage());
					continue;
				}
			}
		}
	}

	private function _replace($replacement, $name, $options = array()) {
		$optString = '';
		if(!empty($options)) {
			$optString = ':' . implode(':', $options);
		}
		$this->_content = str_replace('{$' . $name . $optString . '}', $replacement, $this->_content);
	}
}

