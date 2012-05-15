<?php

/**
 * Description of TCache
 *
 * @author iamne
 */
class Helpers_Action_Cache extends Zend_Controller_Action_Helper_Abstract {

	const KEY_DRAFT                 = 'seotoasterDraftPages';

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

	const CACHE_FLASH               = '300';

	const CACHE_SHORT               = '3600';

	const CACHE_NORMAL              = '43200';

	const CACHE_LONG                = '86400';

	protected $_cache               = null;

	public function  init() {
		$this->_cache = Zend_Registry::get('cache');
	}

	protected function _makeCacheId($string, $cachePrefix = '') {
		return $cachePrefix . preg_replace('/[^_A-Za-z0-9]/', '', $string);
	}

	public function load($cacheKey, $cachePrefix = '') {
		$cahcheId = $this->_makeCacheId($cacheKey, $cachePrefix);
		if(!$this->_cache->test($cahcheId)) {
			return null;
		}
		return $this->_cache->load($cahcheId);
	}

	public function save($cacheId, $data, $cachePrefix = '', $tags = array(), $lifeTime = self::CACHE_SHORT) {
		$cacheId = $this->_makeCacheId($cacheId, $cachePrefix);
		return $this->_cache->save($data, $cacheId, $tags, $lifeTime);
	}

    /**
     * Remove cache matching id, prefix or tags
     * @param string $cacheId cache id to remove
     * @param string $cachePrefix cache prefix to remove
     * @param array $tags array of cache tags to remove
     */
	public function clean($cacheId = '', $cachePrefix = '', $tags = array()) {
		$this->_cache->clean(Zend_Cache::CLEANING_MODE_OLD);
		if(!$cachePrefix && !$cacheId) {
            if (is_array($tags) && !empty($tags)){
                $this->_cache->clean(Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, $tags);
            } else {
                $this->_cache->clean(Zend_Cache::CLEANING_MODE_ALL);
            }
		}
		else {
			$cacheId = $this->_makeCacheId($cacheId, $cachePrefix);
			$this->_cache->remove($cacheId);
		}
	}
}

