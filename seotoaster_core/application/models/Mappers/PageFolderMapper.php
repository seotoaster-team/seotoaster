<?php
/**
 * Class Application_Model_Mappers_PageMapper
 * @method static Application_Model_Mappers_PageFolderMapper getInstance() getInstance() Returns an instance of itself
 * @method Application_Model_DbTable_Page getDbTable() Returns an instance of corresponding DbTable
 */
class Application_Model_Mappers_PageFolderMapper extends Application_Model_Mappers_Abstract
{

    protected $_dbTable = 'Application_Model_DbTable_PageFolder';

    protected $_model = 'Application_Model_Models_PageFolder';

    /**
     * @param Application_Model_Models_PageFolder $folder
     * @return mixed
     * @throws Exceptions_SeotoasterException
     */
    public function save($folder)
    {
        if (!$folder instanceof Application_Model_Models_PageFolder) {
            throw new Exceptions_SeotoasterException('Given parameter should be and Application_Model_Models_PageFolder instance');
        }
        $data = array(
            'name' => $folder->getName(),
            'index_page' => $folder->getIndexPage()
        );


        if ($folder->getId()) {
            $this->getDbTable()->update($data, array('id = ?' => $folder->getId()));
        } else {
            $folderId = $this->getDbTable()->insert($data);
            $folder->setId($folderId);
        }

        return $folder;
    }

    public function delete($id)
    {
        $where = $this->getDbTable()->getAdapter()->quoteInto('id = ?', $id);
        return $this->getDbTable()->getAdapter()->delete('page_folder', $where);
    }

    public function getPageFolders()
    {
        $select = $this->getDbTable()->getAdapter()->select()
            ->from('page_folder', array('id', 'name'));

        return $this->getDbTable()->getAdapter()->fetchPairs($select);
    }

    public function findByName($name)
    {
        $where = $this->getDbTable()->getAdapter()->quoteInto('name = ?', $name);
        return $this->_findWhere($where);
    }

    public function fetchFoldersWithIndexPageUrl()
    {
        $select = $this->getDbTable()->select()->setIntegrityCheck(false)
            ->from(['pf' => 'page_folder'])
            ->joinLeft(['p' => 'page'], 'pf.index_page = p.id', ['url' => 'url']);
        return $this->getDbTable()->getAdapter()->fetchAll($select);
    }
}
