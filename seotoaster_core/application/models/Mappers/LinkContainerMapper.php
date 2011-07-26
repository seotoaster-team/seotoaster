<?php

/**
 * Special mapper. LinkContainerMapper
 *
 * @author Eugene I. Nezhuta [Seotoaster Dev Team] <eugene@seotoaster.com>
 */
class Application_Model_Mappers_LinkContainerMapper extends Application_Model_Mappers_Abstract {

	protected $_dbTable = 'Application_Model_DbTable_LinkContainer';

	public function save($model) {

	}

	public function saveStructured(array $structured) {
		foreach ($structured as $key => $val) {
			foreach($val as $link) {
				$data = array(
					'id_container' => $key,
					'link'         => $link
				);
				try {
					$this->getDbTable()->insert($data);
				}
				catch (Exception $e) {
					$where = sprintf("id_container=%d AND link='%s'", $data['id_container'], $data['link']);
					$this->getDbTable()->update($data, $where);
				}
			}
		}
	}

	public function find($id) {

	}

	public function findById($id) {

	}

	public function findByLink($link) {
		$where = $this->getDbTable()->getAdapter()->quoteInto("link=?", $link);
		return $this->fetchAll($where);
	}

	public function fetchStructured($containerId = 0) {
		$structured = array();
		$entries    = $this->fetchAll(($containerId) ? $this->getDbTable()->getAdapter()->quoteInto('id_container=?', $containerId) : null);
		foreach ($entries as $key => $value) {
			if(!array_key_exists($value['id_container'], $structured)) {
				$structured[$value['id_container']] = array();
			}
			$structured[$value['id_container']][] = $value['link'];
		}
		return $structured;
	}

	public function fetchAll($where = null, $order = array()) {
		$entries = array();
		$resultSet = $this->getDbTable()->fetchAll($where);
		if(null === $resultSet) {
			return null;
		}
		foreach ($resultSet as $row) {
			$entries[] = $row->toArray();
		}
		return $entries;
	}

	public function delete($containerId, $links) {
		$where = $this->getDbTable()->getAdapter()->quoteInto('id_container=?', $containerId);
		if(is_array($links)) {
			foreach ($links as $link) {
				$where = $this->getDbTable()->getAdapter()->quoteInto('id_container=?', $containerId) . ' AND ' . $this->getDbTable()->getAdapter()->quoteInto("link=?", $link);
				$this->getDbTable()->delete($where);
			}
			return true;
		}
		$where = $this->getDbTable()->getAdapter()->quoteInto('id_container=?', $containerId) . ' AND ' . $this->getDbTable()->getAdapter()->quoteInto("link=?", $links);
		return $this->getDbTable()->delete($where);
	}

	public function deleteByContainerId($id) {
		$where = $this->getDbTable()->getAdapter()->quoteInto('id_container=?', $id);
		return $this->getDbTable()->delete($where);
	}
}

