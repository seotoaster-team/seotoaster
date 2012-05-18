<?php

/**
 * GarbageCollector
 *
 * @author Seotoaster Dev Team
 */
class Tools_Content_GarbageCollector extends Tools_System_GarbageCollector {


	protected function _runOnDefault() {

	}

	protected function _runOnUpdate() {
		$this->_updateContentLinksRelatios();
		$this->_cleanEmptyContainer();
		$this->_trimWidgets();
		$this->_cleanCached();
		$this->_resetSearchIndexRenewFlag();
	}

	public function updateContentLinksRelatios() {
		$this->_updateContentLinksRelatios();
	}

	protected function _runOnDelete() {
		$this->_resetSearchIndexRenewFlag();
	}

	private function _updateContentLinksRelatios() {
		if(!$this->_object instanceof Application_Model_Models_Container) {
			throw new Exceptions_SeotoasterException('Wrong object given. Instance of Application_Model_Models_Container expected.');
		}

		$links                         = array();
		$mapper                        = Application_Model_Mappers_LinkContainerMapper::getInstance();
		$links[$this->_object->getId()]= Tools_Content_Tools::findLinksInContent($this->_object->getContent(), true);
		$containerId                   = $this->_object->getId();
		$containerLinks                = $mapper->fetchStructured($containerId);
		if(is_array($containerLinks) && isset ($containerLinks[$containerId])) {
			$diff = array_diff($containerLinks[$containerId], $links[$containerId]);
			$mapper->delete($containerId, $diff);
		}
		return $mapper->saveStructured($links);
	}

	private function _trimWidgets() {
		$content = $this->_object->getContent();
		if($content) {
			$content = str_replace('<p>{', '{', $content);
			$this->_object->setContent(str_replace('}</p>', '}', $content));
			Application_Model_Mappers_ContainerMapper::getInstance()->save($this->_object);
		}
	}

	public function _cleanEmptyContainer() {
		if(!$this->_object->getContent()) {
			$this->_object->removeObserver($this);
			Application_Model_Mappers_ContainerMapper::getInstance()->delete($this->_object);
		}
	}

	private function _resetSearchIndexRenewFlag() {
		$cacheHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('cache');
		$cacheHelper->clean(null, null, array('search_index_renew'));
	}

	private function _cleanCached(){
		$cacheId   =  $this->_object->getName() . intval($this->_object->getPageId()) . $this->_object->getContainerType();
		$cacheHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('cache');
		$cacheHelper->clean($cacheId, 'widget_');
	}

}

