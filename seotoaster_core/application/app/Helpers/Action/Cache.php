<?php
/**
 * Cache helper allows to save, load and clean cached data
 *
 * @author iamne
 */
class Helpers_Action_Cache extends Zend_Controller_Action_Helper_Abstract
{
    const KEY_DRAFT                 = 'seotoasterDraftPages';

    const KEY_DRAFT_COUNT           = 'seotoasterDraftPagesCount';

    const TAG_DRAFT                 = 'tagDraftPages';

    const PREFIX_DRAFT              = 'draft_';

    const KEY_PLUGINTABS            = 'pluginsExtraTabs';

    const PREFIX_PLUGINTABS         = 'plugtabs_';

    const KEY_PLUGINEDITOR_LINKS    = 'pluginsExtraEditorLinks';

    const PREFIX_PLUGINEDITOR_LINKS = 'plugedlinks_';

    const KEY_PLUGINEDITOR_TOP      = 'pluginsExtraEditorTop';

    const PREFIX_PLUGINEDITOR_TOP   = 'plugedtop_';

    const KEY_DEEPLINKS             = 'seotoasterDeeplinks';

    const PREFIX_DEEPLINKS          = 'deeplinks_';

    const PREFIX_WIDGET             = 'widget_';

    const PREFIX_SITEMAPS           = 'sitemaps_';

    const PREFIX_FEEDS              = 'feeds_';

    const CACHE_FLASH               = '300';

    const CACHE_SHORT               = '3600';

    const CACHE_NORMAL              = '43200';

    const CACHE_LONG                = '86400';

    const CACHE_WEEK                = '604800';

    /**
     * @var null|Zend_Cache_Core
     */
    protected $_cache               = null;

    public function  init()
    {
        $this->_cache = Zend_Registry::get('cache');
        return $this;
    }

    protected function _makeCacheId($string, $cachePrefix = '')
    {
        return $cachePrefix.preg_replace('/[^_A-Za-z0-9]/', '', $string);
    }

    /**
     * Load data from cache
     * @param $cacheKey           Unique cache id
     * @param string $cachePrefix Cache prefix
     * @return null               Returns null if nothing found
     */
    public function load($cacheKey, $cachePrefix = '')
    {
        $cacheId = $this->_makeCacheId($cacheKey, $cachePrefix);
        if (!$this->_cache->test($cacheId)) {
            return null;
        }
        
        return $this->_cache->load($cacheId);
    }

    /**
     * Save data to cache
     * @param $cacheId            Unique cache identifier
     * @param $data               Content to be cached
     * @param string $cachePrefix Prefix for cache id
     * @param array $tags         Cache tags
     * @param string $lifeTime    Lifetime for cache record
     * @return boolean            True if no problem
     */
    public function save($cacheId, $data, $cachePrefix = '', $tags = array(), $lifeTime = self::CACHE_FLASH)
    {
        $cacheId = $this->_makeCacheId($cacheId, $cachePrefix);
        return $this->_cache->save($data, $cacheId, $this->_sanitizeTags($tags), $lifeTime);
    }

    /**
     * Update data to cache
     * @param $cacheId            Unique cache identifier
     * @param $key                Unique key
     * @param $data               Content to be cached
     * @param string $cachePrefix Prefix for cache id
     * @param array $tags         Cache tags
     * @param string $lifeTime    Lifetime for cache record
     * @return array/boolean      Array if no problem
     */
    public function update($cacheId, $key, $data, $cachePrefix = '', $tags = array(), $lifeTime = self::CACHE_FLASH)
    {
        if (($cacheData = $this->load($cacheId, $cachePrefix)) === null) {
            $cacheData = array(
                'tags' => array(),
                'data' => array()
            );
        }
        if (is_array($tags) && !empty($tags)) {
            $cacheData['tags'] = array_merge(
                $cacheData['tags'],
                (!empty($cacheData['tags'])) ? array_diff($tags, $cacheData['tags']) : $tags
            );
        }
        $cacheData['data'][$key] = $data;
        $status = $this->_cache->save(
            $cacheData,
            $this->_makeCacheId($cacheId, $cachePrefix),
            $this->_sanitizeTags($cacheData['tags']),
            $lifeTime
        );

        return ($status) ? $cacheData : $status;
    }

    /**
     * Remove cache matching id, prefix or tags
     * @param string $cacheId     cache id to remove
     * @param string $cachePrefix cache prefix to remove
     * @param array $tags         array of cache tags to remove
     */
    public function clean($cacheId = '', $cachePrefix = '', $tags = array())
    {
        $tags = $this->_sanitizeTags($tags);
        $this->_cache->clean(Zend_Cache::CLEANING_MODE_OLD);
        if (!$cachePrefix && !$cacheId) {
            if (is_array($tags) && !empty($tags)) {
                $this->_cache->clean(Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, $tags);
            }
            else {
                $this->_cache->clean(Zend_Cache::CLEANING_MODE_ALL);
            }
        }
        else {
            $cacheId = $this->_makeCacheId($cacheId, $cachePrefix);
            $this->_cache->remove($cacheId);
        }
    }

    private function _sanitizeTags($tags)
    {
        if (is_array($tags)) {
            return preg_replace('/[^\w\d_]/', '', $tags);
        }

        return array();
    }
}
