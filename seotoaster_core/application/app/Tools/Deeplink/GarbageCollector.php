<?php

/**
 * GarbageCollector
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Tools_Deeplink_GarbageCollector extends Tools_System_GarbageCollector {


	protected function _runOnDefault() {

	}

	protected function _runOnDelete() {
		$this->_removeDeeplinkOccurrences();
	}

	private function _removeDeeplinkOccurrences() {
		if(!$this->_object instanceof Application_Model_Models_Deeplink) {
			throw new Exceptions_SeotoasterException('Wrong object given. Instance of Application_Model_Models_Deeplink expected.');
		}
		$websiteHelper     = Zend_Controller_Action_HelperBroker::getStaticHelper('Website');
		$linksContainerMap = Application_Model_Mappers_LinkContainerMapper::getInstance()->findByLink((($this->_object->getType() == Application_Model_Models_Deeplink::TYPE_INTERNAL) ? $websiteHelper->getUrl() . $this->_object->getUrl() : $this->_object->getUrl()));
		if(!empty ($linksContainerMap)) {
			$containerMapper = Application_Model_Mappers_ContainerMapper::getInstance();
			foreach ($linksContainerMap as $item) {
				$container = $containerMapper->find($item['id_container']);
				$container->registerObserver(new Tools_Content_GarbageCollector(array(
					'action' => Tools_System_GarbageCollector::CLEAN_ONUPDATE
				)));
				//$deeplinkRemovalPattern = '~<a\s+.*\s+(title=".+")*\s*.*\s+href="' . $item['link'] . '"\s*(title=".*")*\s*>' . $this->_object->getName() . '</a>~usU';
				$deeplinkRemovalPattern = '~<a(\s+|[^\>]+\s+)href="' . $item['link'] . '"\s*[^\>]*>' . $this->_object->getName() . '</a>~usU';
				if(preg_match($deeplinkRemovalPattern, $container->getContent(), $matches)) {
					$containerMapper->save($container->setContent(str_replace($matches[0], $this->_object->getName(), $container->getContent())));
					$container->notifyObservers();
				}
			}
		}
	}

}

