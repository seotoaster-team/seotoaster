<?php
/**
 * Description of Abstract
 *
 * @author iamne
 */
abstract class Widgets_Abstract implements Zend_Acl_Resource_Interface
{
    /**
     * Instance of Zend_View
     *
     * @var Zend_View
     */
    protected $_view           = null;

    protected $_options        = null;

    protected $_toasterOptions = null;

    protected $_cache          = null;

    protected $_cacheId        = null;

    protected $_cachePrefix    = 'widgets_';

    protected $_cacheable      = true;

    protected $_cacheTags      = array();

    protected $_cacheLifeTime  = Helpers_Action_Cache::CACHE_WEEK;

    protected $_widgetId       = null;

    /**
     * Instance of the Zend_Translate
     *
     * @var mixed|Zend_Translate
     */
    protected $_translator = null;

    public function __construct($options = null, $toasterOptions = array())
    {
        $this->_options        = $options;
        $this->_toasterOptions = $toasterOptions;

        if ($this->_cacheable === true) {
            $roleId = Zend_Controller_Action_HelperBroker::getStaticHelper('Session')->getCurrentUser()->getRoleId();
            $this->_cache     = Zend_Controller_Action_HelperBroker::getStaticHelper('Cache');
            $this->_widgetId  = strtolower(get_called_class());
            $this->_widgetId .= (!empty($this->_options) ? '-'.implode('-', $this->_options) : '');

            if (isset($toasterOptions['id'])) {
                $this->_cacheId = 'page-'.$toasterOptions['id'].'-'.$roleId;
            }
            else {
                $this->_cacheId = 'widget-'.$this->_widgetId.'-'.$roleId;
            }
            $this->_cacheId .= '-lifeTime-'.$this->_cacheLifeTime;
        }

        $this->_translator = Zend_Registry::get('Zend_Translate');
        $this->_init();
    }

    protected function _init()
    {

    }

    public function getResourceId()
    {
        return Tools_Security_Acl::RESOURCE_WIDGETS;
    }

    public function render()
    {
        if ($this->_cacheable) {
            $data = $this->_loadFromCache();
            if (isset($data[$this->_widgetId])) {
                $content = $data[$this->_widgetId];
            }
            else {
                if ($data === null) {
                    $data = array();
                }
                try {
                    $data[$this->_widgetId] = $this->_load();
                    $this->_cache->save(
                        $this->_cacheId,
                        $data,
                        $this->_cachePrefix,
                        is_array($this->_cacheTags) ? $this->_cacheTags : array(),
                        $this->_cacheLifeTime
                    );
                    $content = $data[$this->_widgetId];
                }
                catch (Exceptions_SeotoasterException $ste) {
                    $content = $ste->getMessage();
                }
            }
        }
        else {
            $content = $this->_load();
        }

        return $content;
    }

    protected function _loadFromCache()
    {
        return $this->_cache->load($this->_cacheId, $this->_cachePrefix);
    }

    abstract protected function _load();
}
