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

	}

	protected function _runOnDelete() {

	}

	private function _updateContentLinksRelatios() {
		if(!$this->_object instanceof Application_Model_Models_Container) {
			throw new Exceptions_SeotoasterException('Wrong object given. Instance of Application_Model_Models_object expected.');
		}

		$links                         = array();
		$mapper                        = new Application_Model_Mappers_LinkContainerMapper();
		$links[$this->_object->getId()]= Tools_Content_Tools::findLinksInContent($this->_object->getContent(), true);
		$containerId                   = $this->_object->getId();
		$containerLinks                = $mapper->fetchStructured($containerId);
		if(is_array($containerLinks) && isset ($containerLinks[$containerId])) {
			$diff = array_diff($containerLinks[$containerId], $links[$containerId]);
			$mapper->delete($containerId, $diff);
		}
		return $mapper->saveStructured($links);
	}

	public function _cleanEmptyContainer() {
		if(!$this->_object->getContent()) {
			$this->_object->removeObserver($this);
			$mapper = new Application_Model_Mappers_ContainerMapper();
			$mapper->delete($this->_object);
		}
	}

}

