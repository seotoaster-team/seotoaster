<?php
/**
 * Container mapper
 *
 * @author Seotoaster Dev Team
 * @method static Application_Model_Mappers_ContainerMapper getInstance() getInstance() Returns an instance of itself
 * @method Application_Model_DbTable_Container getDbTable() Returns an instance of corresponding DbTable
 */
class Application_Model_Mappers_ContainerMapper extends Application_Model_Mappers_Abstract {

	protected $_dbTable = 'Application_Model_DbTable_Container';

	protected $_model   = 'Application_Model_Models_Container';

	public function save($container) {
		if(!$container instanceof Application_Model_Models_Container) {
			throw new Exceptions_SeotoasterException('Given parameter should be and Application_Model_Models_Container instance');
		}
		$data = array(
			'name'            => $container->getName(),
			'content'         => $container->getContent(),
			'container_type'  => $container->getContainerType(),
			'page_id'         => $container->getPageId(),
			'published'       => $container->getPublished(),
			'publishing_date' => $container->getPublishingDate()
		);
		if(!$container->getId()) {
			return $this->getDbTable()->insert($data);
		}
		else {
			return $this->getDbTable()->update($data, array('id = ?' => $container->getId()));
		}
	}

	public function findByName($name, $pageId = 0, $type = Application_Model_Models_Container::TYPE_REGULARCONTENT) {
		$where = $this->getDbTable()->getAdapter()->quoteInto('name = ?', $name);
		$where .= ' AND ' . $this->getDbTable()->getAdapter()->quoteInto('container_type = ?', $type);
        if ($pageId
            && $type != Application_Model_Models_Container::TYPE_STATICCONTENT
            && $type != Application_Model_Models_Container::TYPE_STATICHEADER
        ) {
			$where .= ' AND ' . $this->getDbTable()->getAdapter()->quoteInto('page_id = ?', $pageId);
		}
		$row  = $this->getDbTable()->fetchAll($where)->current();
		if(null == $row) {
			return null;
		}
		return new Application_Model_Models_Container($row->toArray());
	}

	public function findByPageId($pageId) {
		$where = $this->getDbTable()->getAdapter()->quoteInto("page_id = ?", $pageId);
		return $this->fetchAll($where);
	}

	public function findContentContainersByPageId($pageId) {
		$where  = $this->getDbTable()->getAdapter()->quoteInto("page_id = ?", $pageId);
		$where .= ' AND ' . $this->getDbTable()->getAdapter()->quoteInto('container_type != ?', Application_Model_Models_Container::TYPE_REGULARHEADER);
		$where .= ' AND ' . $this->getDbTable()->getAdapter()->quoteInto('container_type != ?', Application_Model_Models_Container::TYPE_STATICHEADER);
		$where .= ' AND ' . $this->getDbTable()->getAdapter()->quoteInto('container_type != ?', Application_Model_Models_Container::TYPE_CODE);
		return $this->fetchAll($where);
	}
    
    public function findPreposByPageId($pageId) {
		$where  = $this->getDbTable()->getAdapter()->quoteInto("page_id = ?", $pageId);
		$where .= ' AND ' . $this->getDbTable()->getAdapter()->quoteInto('container_type = ?', Application_Model_Models_Container::TYPE_PREPOP);
		return $this->fetchAll($where);
	}

	public function deleteByPageId($pageId) {

	}

	public function delete(Application_Model_Models_Container $container) {
		$where = $this->getDbTable()->getAdapter()->quoteInto("id = ?", $container->getId());
		$this->getDbTable()->delete($where);
		$container->notifyObservers();
	}

	/**
	 * Method finds container which contains query string inside itself
	 * @param string $findString String to be find
	 * @param boolean $attachPage Flag to attach page or not
	 * @return mixed Array of containers objects or null if no matches found.
	 */
	public function findByContent($findString, $attachPage = false) {
		$where = $this->getDbTable()->getAdapter()->quoteInto("content LIKE ?", '%'.$findString.'%');
		$row = $this->fetchAll($where);
		if (empty($row)){
			return null;
		}
		return $row;
	}
    
    public function findByContainerName($name, $unique = false){
        $where = $this->getDbTable()->getAdapter()->quoteInto('name = ?', $name);
        $where .= ' AND ' . $this->getDbTable()->getAdapter()->quoteInto('container_type = ?', Application_Model_Models_Container::TYPE_PREPOP);
        if($unique){
            $select = $this->getDbTable()->getAdapter()
                ->select()
                ->from(array('container'))
                ->where($where)
                ->where('content IS NOT NULL')
                ->where('content != ""')
                ->order('content ASC')
                ->group(array('content'));
            return $this->getDbTable()->getAdapter()->fetchAll($select);
        } else {
            return $this->fetchAll($where);
        }
    }
    
    public function findByContainerNames($prepopNames = array()){
        if(!empty($prepopNames)){
            $select = $this->getDbTable()->getAdapter()
                    ->select()
                    ->from(array('container'))
                    ->where('name IN (?)', $prepopNames)
                    ->where('container_type = ?', Application_Model_Models_Container::TYPE_PREPOP)
                    ->where('content IS NOT NULL')
                    ->where('content != ""')
                    ->order('content ASC');
            return $this->getDbTable()->getAdapter()->fetchAll($select);
        }
    }
    
    public function findByContainerNameWithContent($containerContentArray){
        $pageId = array();
        $pageIdArray = array();
        $summaryArray = array();
        $start = 0;
        foreach($containerContentArray as $container=>$content){
            if($content != 'select'){
                $where = $this->getDbTable()->getAdapter()->quoteInto('name = ?', $container);
                $where .= ' AND ' . $this->getDbTable()->getAdapter()->quoteInto("content = ?", $content);
                $where .= ' AND ' . $this->getDbTable()->getAdapter()->quoteInto('container_type = ?', Application_Model_Models_Container::TYPE_PREPOP);
                $result = $this->fetchAll($where);
                if(!empty($result)){
                   $summaryArray = array();
                   foreach($result as $page){
                       if($start == 0){
                           $pageId[$page->getPageId()] = $page->getPageId();
                       }
                       if(in_array($page->getPageId() ,$pageId) && $start != 0){
                           $pageIdArray[$page->getPageId()] = $page->getPageId();
                           $summaryArray[$page->getPageId()] = $page->getPageId();
                       }
                    }
                    if($start != 0 && empty($summaryArray)){
                        return array();
                    }
                    if($start != 0 && !empty($summaryArray)){
                        $pageId = $summaryArray;
                    }
                    $start++;

                }else{
                    return array();
                }

            }
            
        }
        if($start == 1){
           return $pageId;
        }
        return $summaryArray;
    }
        
}