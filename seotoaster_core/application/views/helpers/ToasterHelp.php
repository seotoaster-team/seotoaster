<?php
/**
 * ToasterHelp
 * @author: iamne <eugene@seotoaster.com> Seotoaster core team
 * Date: 5/28/12
 * Time: 6:35 PM
 */
class Zend_View_Helper_ToasterHelp extends Zend_View_Helper_Abstract
{

    const REMOTE_URL = 'http://www.seotoaster.com/';

    const SECTION_ADDPAGE = 'addpage';

    const SECTION_EDITPAGE = 'editpage';

    const SECTION_ORGANIZE = 'organize';

    const SECTION_DRAFT = 'draft';

    const SECTION_UPLOADTHINGS = 'uploadthings';

    const SECTION_REMOVETHINGS = 'removethings';

    const SECTION_ROBOTS = 'robots';

    const SECTION_301S = '301s';

    const SECTION_DEEPLINKS = 'deeplinks';

    const SECTION_SCULPTING = 'sculpting';

    const SECTION_ADDTEMPLATE = 'addtemplate';

    const SECTION_EDITTEMPLATE = 'edittemplate';

    const SECTION_EDITCSS = 'editcss';

    const SECTION_THEMES = 'themes';

    const SECTION_CONFIG = 'config';

    const SECTION_USERS = 'users';

    const SECTION_PLUGINS = 'plugins';

    const SECTION_FA = 'fa';

    const SECTION_EDITCONTENT = 'content';

    const SECTION_ACTIONEMAILS = 'actionemails';

    const SECTION_EDITFORM = 'editform';

    const SECTION_WIDCARD = 'widcard';

    const SECTION_UPDATER = 'updater';

    private $_helpHashMap = array(
        self::SECTION_ADDPAGE      => 'how-to-create-and-edit-a-page.html',
        self::SECTION_EDITPAGE     => 'how-to-create-and-edit-a-page.html',
        self::SECTION_ORGANIZE     => 'how-to-organize-pages.html',
        self::SECTION_DRAFT        => 'how-to-set-page-as-draft.html',
        self::SECTION_UPLOADTHINGS => 'how-to-upload-things.html',
        self::SECTION_REMOVETHINGS => 'how-to-remove-things.html',
        self::SECTION_ROBOTS       => 'how-to-edit-robottxt.html',
        self::SECTION_301S         => 'automated-301-redirects.html',
        self::SECTION_DEEPLINKS    => 'deep-linking.html',
        self::SECTION_SCULPTING    => 'link-siloing.html',
        self::SECTION_ADDTEMPLATE  => 'how-to-add-and-edit-a-template.html',
        self::SECTION_EDITTEMPLATE => 'how-to-add-and-edit-a-template.html',
        self::SECTION_EDITCSS      => 'how-to-edit-css.html',
        self::SECTION_THEMES       => 'how-to-add-and-change-theme.html',
        self::SECTION_CONFIG       => 'manage-config.html',
        self::SECTION_USERS        => 'manage-users.html',
        self::SECTION_PLUGINS      => 'how-to-add-a-plugin.html',
        self::SECTION_FA           => 'how-to-add-and-edit-a-featured-area.html',
        self::SECTION_ACTIONEMAILS => 'action-emails-cheat-sheet.html',
        self::SECTION_EDITCONTENT  => 'add-and-edit-content.html',
        self::SECTION_EDITFORM     => 'how-to-add-a-form.html',
        self::SECTION_WIDCARD      => 'local-search-engine-optimization.html',
        self::SECTION_UPDATER      => 'updater.html'
    );

    public function toasterHelp($section, $hashMap = null)
    {
        if (is_array($hashMap)) {
            $this->_helpHashMap = array_merge($this->_helpHashMap, $hashMap);
        }
        if (array_key_exists($section, $this->_helpHashMap)) {
            return '<a class="help ticon-help" href="' . self::REMOTE_URL . $this->_helpHashMap[$section] . '" target="_blank"></a>';
        }
        return '<a class="help ticon-help" href="javascript:;"></a>';
    }

}
