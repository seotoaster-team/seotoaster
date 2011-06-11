<?php

/**
 * Description of TCache
 *
 * @author iamne
 */
class Helpers_Action_Cache extends Zend_Controller_Action_Helper_Abstract {

	const CACHE_FLASH = '300';

	const CACHE_SHORT = '3600';

	const CACHE_LONG  = '86400';

	protected $_cache = null;

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

	public function save($cacheId, $data, $cachePrefix = '', $tags = array(), $lifeTime = '') {
		$cacheId = $this->_makeCacheId($cacheId, $cachePrefix);
		return $this->_cache->save($data, $cacheId, $tags, $lifeTime);
	}

	public function clean($cacheId = '', $cachePrefix = '') {
		$this->_cache->clean(Zend_Cache::CLEANING_MODE_OLD);
		if(!$cachePrefix && !$cacheId) {
			$this->_cache->clean(Zend_Cache::CLEANING_MODE_ALL);
		}
		else {
			$cacheId = $this->_makeCacheId($cacheId, $cachePrefix);
			$this->_cache->remove($cacheId);
		}
	}
}

