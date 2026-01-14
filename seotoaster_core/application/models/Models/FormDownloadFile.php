<?php

/**
 * FormDownloadFile.php model
 *
 */
class Application_Model_Models_FormDownloadFile extends Application_Model_Models_Abstract {

	protected $_pageId        = '';

	protected $_formName      = '';

	protected $_fileFolder    = '';

	protected $_fileName      = '';

    /**
     * @return int
     */
    public function getPageId()
    {
        return $this->_pageId;
    }

    /**
     * @param int $pageId
     */
    public function setPageId($pageId)
    {
        $this->_pageId = $pageId;
    }

    /**
     * @return string
     */
    public function getFormName()
    {
        return $this->_formName;
    }

    /**
     * @param string $formName
     */
    public function setFormName($formName)
    {
        $this->_formName = $formName;
    }

    /**
     * @return string
     */
    public function getFileFolder()
    {
        return $this->_fileFolder;
    }

    /**
     * @param string $fileFolder
     */
    public function setFileFolder($fileFolder)
    {
        $this->_fileFolder = $fileFolder;
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->_fileName;
    }

    /**
     * @param string $fileName
     */
    public function setFileName($fileName)
    {
        $this->_fileName = $fileName;
    }


}

