<?php
/**
 * Tools
 *
 * @author Eugene I. Nezhuta <theneiam@gmail.com>
 * Date: 12/14/12
 * Time: 10:36 AM
 */
class Tools_Template_Tools {

    const THEME_CONFIGURATION_FILE = 'theme.ini';

    /**
     * List of required system templates
     *
     * @var array
     */
    public static $protectedTemplates = array('index', 'default', 'category');

    /**
     *
     *
     */
    public static function findPreview() {

    }

    public static function toArray($template, $currentTemplate = null) {
        return array(
            'id'         => $template->getId(),
            'name'	     => $template->getName(),
            'isCurrent'  => ($currentTemplate && ($template->getName() == $currentTemplate->getName())) ? true : false,
            'pagesCount' => Tools_Page_Tools::getPagesCountByTemplate($template->getName()),
            'type'       => $template->getType(),
            'content'    => $template->getContent(),
            'protected'  => in_array($template->getName(), self::$protectedTemplates)
            //'preview_image' => isset($tmplImages[$template->getName()]) ? $this->_themeConfig['path'].$currentTheme.'/'.$this->_themeConfig['templatePreview'].$tmplImages[$template->getName()] : false, //'system/images/no_preview.png'
        );
    }

}
