<?php

/**
 * Seotoaster 2 parser
 *
 * @author Seotoaster Dev Team (SDT)
 */
class Tools_Content_Parser
{
    const PARSE_DEEP        = 5;

    const OPTIONS_SEPARATOR = ':';

    const MAGIC_SPACE_LABEL = 'magic';

    protected $_pageData      = null;

    protected $_content       = null;

    protected $_options       = array();

    protected $_iteration     = 0;

    public function  __construct($content = null, $pageData = null, $options = null)
    {
        if (null !== $content) {
            $this->_content  = $content;
        }
        if (null !== $pageData) {
            $this->_pageData = $pageData;
        }
        if (null !== $options) {
            if (!is_array($options)) {
                throw new Exceptions_SeotoasterException('Parser options should be an array');
            }
            $this->_options  = $options;
        }
    }

    public function parse()
    {
        $this->_runMagicSpaces(true);
        $this->_runWidgets();
        $this->_changeMedia();
        $this->_runMagicSpaces();

        return $this->_content;
    }

    public function getPageData()
    {
        return $this->_pageData;
    }

    public function setPageData(array $pageData)
    {
        $this->_pageData = $pageData;
        return $this;
    }

    public function getContent()
    {
        return $this->_content;
    }

    public function setContent($content)
    {
        if (empty($content)) {
            throw new Exceptions_SeotoasterException('Content passed to the parse is empty');
        }
        $this->_content = $content;
        return $this;
    }

    public function getOptions()
    {
        return $this->_options;
    }

    public function setOptions(array $options)
    {
        $this->_options = $options;
        return $this;
    }

    /**
     * Simple parsing of content. No theme and media changes, no magic space proccessing
     *
     * @return null
     */
    public function parseSimple()
    {
        $this->_runMagicSpaces(true);
        $this->_runWidgets();
        $this->_runMagicSpaces();

        return $this->_content;
    }

    /**
     * @return $this
     */
    protected function _resetIteration()
    {
        $this->_iteration = 0;
        return $this;
    }

    protected function _runWidgets()
    {
        if (($this->_iteration++) > self::PARSE_DEEP) {
            return $this->_resetIteration();
        }


        $widgets = $this->_findWidgets();
        if (empty($widgets)) {
            return $this->_resetIteration();
        }

        foreach ($widgets as $widgetData) {
            try {
                $widget = Tools_Factory_WidgetFactory::createWidget(
                    $widgetData['name'],
                    $widgetData['options'],
                    array_merge($this->_pageData, $this->_options)
                );
                $replacement = (is_object($widget)) ? $widget->render() : $widget;
            }
            catch (Exceptions_SeotoasterException $se) {
                if (Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_CONTENT)) {
                    $replacement = $se->getMessage() . ' Can not load widget: <b>' . $widgetData['name'] . '</b>';
                } else {
                    $replacement = '';
                }
            }
            $this->_replace($replacement, $widgetData['name'], $widgetData['options']);
        }
        $this->_runWidgets();
    }

    protected function _changeMedia()
    {
        $webPathToTheme = $this->_options['websiteUrl'].$this->_options['themePath']
            .rawurlencode($this->_options['currentTheme']);
        $this->_content = preg_replace(
            '~["\']+/*(mobile/)?images/(.*)["\']~Usui',
            $webPathToTheme . '/$1images/$2',
            $this->_content
        );
        $this->_content = preg_replace(
            '~(<link.*?href=")(\/{0,1}[A-Za-z0-9.\/_\-]+\.css[a-z0-9=\?]*)(".*?>)~Uu',
            '$1'.$webPathToTheme.'/$2'.'$3',
            $this->_content
        );
        $this->_content = preg_replace(
            '~(<script.*?src=")(\/{0,1}[A-Za-z0-9-\/_.\-]+\.js)(".*?>)~ui',
            '$1'.$webPathToTheme.'/$2'.'$3',
            $this->_content
        );
        $favicoPath     = $this->_options['themePath'].$this->_options['currentTheme'].'/favicon.ico';
        if (file_exists($favicoPath)) {
            $this->_content = preg_replace(
                '~href="\/favicon.ico"~Uus',
                'href="'.$webPathToTheme.'/favicon.ico"',
                $this->_content
            );
        }
    }

    protected function _findWidgets()
    {
        $widgets = array();
        preg_match_all('/{\$([\w]+:*[^{}]*)}/ui', $this->_content, $found);
        if (!empty ($found) && isset($found[1])) {
            foreach ($found[1] as $widgetString) {
                $expWidgetString = explode(':', $widgetString);
                $widgetName      = array_shift($expWidgetString);
                $widgetOptions   = ($expWidgetString) ? $expWidgetString : array();
                $widgets[]       = array(
                    'name'    => $widgetName,
                    'options' => $widgetOptions
                );
            }
        }
        return $widgets;
    }

    protected function _runMagicSpaces($parseBefore = false, $excludeFromParse = array())
    {
        if (($this->_iteration++) > self::PARSE_DEEP) {
            return $this->_resetIteration();
        }

        preg_match_all(
            '~(?:[^{]{([\w]+'.self::OPTIONS_SEPARATOR.'*[:\w\-\s,&]*)})~uiUs',
            $this->_content,
            $spacesFound
        );
        $spacesFound = array_filter($spacesFound[1]);
        if (!empty($excludeFromParse)) {
            $spacesFound = array_diff($spacesFound, $excludeFromParse);
        }
        if (empty($spacesFound)) {
            return $this->_resetIteration();
        }

        foreach ($spacesFound as $key => $spaceName) {
            // If any parameters passed
            $parameters = explode(self::OPTIONS_SEPARATOR, $spaceName);
            $magicLabel = false;
            if (is_array($parameters)) {
                $spaceName = array_shift($parameters);
                if ($spaceName === self::MAGIC_SPACE_LABEL) {
                    $spaceName  = array_shift($parameters);
                    $magicLabel = true;
                }
            }

            try {
                $magicSpaceObj = Tools_Factory_MagicSpaceFactory::createMagicSpace(
                    $spaceName,
                    $this->_content,
                    array_merge($this->_pageData, $this->_options),
                    $parameters,
                    $magicLabel
                );

                // Check parse before or after parse widgets and is allowed recursive parse
                if (($parseBefore === true && $parseBefore !== $magicSpaceObj->isAllowedParseBefore())
                    || (!$magicSpaceObj->isAllowedRecursiveParse() && $this->_iteration > 1)
                ) {
                    array_push($excludeFromParse, $spacesFound[$key]);
                    continue;
                }

                $this->_content = $magicSpaceObj->run();
            }
            catch (Exception $e) {
                Tools_System_Tools::debugMode() && error_log($e->getMessage());
                continue;
            }
        }

        $this->_runMagicSpaces($parseBefore, $excludeFromParse);
    }

    protected function _replace($replacement, $name, $options = array())
    {
        $optString = '';
        if (!empty($options)) {
            $optString = self::OPTIONS_SEPARATOR.implode(self::OPTIONS_SEPARATOR, $options);
        }

        $this->_content = str_replace('{$'.$name.$optString.'}', $replacement, $this->_content);
    }
}
