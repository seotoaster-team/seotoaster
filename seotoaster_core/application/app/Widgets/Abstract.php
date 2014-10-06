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

    protected $_cacheData      = array();

    protected $_widgetId       = null;

    protected $_developerModeStatus = false;

    /**
     * Instance of the Zend_Translate
     *
     * @var mixed|Zend_Translate
     */
    protected $_translator     = null;

    public function __construct($options = null, $toasterOptions = array())
    {
        $this->_options        = $options;
        $this->_toasterOptions = $toasterOptions;

        /** Check developer mode status. */
        $this->_developerModeStatus = (bool)Zend_Controller_Action_HelperBroker::getStaticHelper('config')->getConfig('enableDeveloperMode');

        $this->_setDeveloperModeProp();

        if ($this->_cacheable === true) {
            $roleId = Zend_Controller_Action_HelperBroker::getStaticHelper('Session')->getCurrentUser()->getRoleId();
            $this->_cache     = Zend_Controller_Action_HelperBroker::getStaticHelper('Cache');
            $this->_widgetId  = strtolower(get_called_class());
            $this->_widgetId .= (!empty($this->_options) ? '_'.implode('_', $this->_options) : '');

            if (isset($toasterOptions['id'])) {
                $this->_cacheId = 'page_'.$toasterOptions['id'];
            }
            else {
                $this->_cacheId = strtolower(get_called_class());
            }
            $this->_cacheId .= '_'.$roleId.'_lifeTime_'.$this->_cacheLifeTime;
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
        $this->_setDeveloperModeProp();

        if ($this->_cacheable) {
            $this->_cacheData = $this->_loadFromCache();
            if (isset($this->_cacheData['data'][$this->_widgetId])) {
                $content = $this->_cacheData['data'][$this->_widgetId];
            }
            else {
                try {
                    $content = $this->_load();

                    if ($this->_cacheData === null) {
                        $this->_cacheData = array(
                            'tags' => array(),
                            'data' => array()
                        );
                    }

                    if (is_array($this->_cacheTags) && !empty($this->_cacheTags)) {
                        $this->_cacheData['tags'] = array_merge(
                            $this->_cacheData['tags'],
                            (!empty($this->_cacheData['tags']))
                                ? array_diff($this->_cacheTags,  $this->_cacheData['tags'])
                                : $this->_cacheTags
                        );
                    }

                    $this->_cacheData['data'][$this->_widgetId] = $content;
                    $this->_cache->save(
                        $this->_cacheId,
                        $this->_cacheData,
                        $this->_cachePrefix,
                        $this->_cacheData['tags'],
                        $this->_cacheLifeTime
                    );
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

    protected function _setDeveloperModeProp()
    {
        if ($this->_developerModeStatus) {
            $this->_cacheable = false;
        }
    }

    protected function _loadFromCache()
    {
        return $this->_cache->load($this->_cacheId, $this->_cachePrefix);
    }

    abstract protected function _load();
}
