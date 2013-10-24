<?php
/**
 * Seotoaster content API
 *
 * @author Eugene I. Nezhuta <eugene@seotoaster.com>
 * Date: 9/26/13
 * Time: 3:51 PM
 */

class Api_Toaster_Containers extends Api_Service_Abstract {

    protected $_accessList = array(
        Tools_Security_Acl::ROLE_SUPERADMIN => array('allow' => array('get', 'post', 'put', 'delete')),
        Tools_Security_Acl::ROLE_ADMIN      => array('allow' => array('get', 'post', 'put', 'delete')),
        Tools_Security_Acl::ROLE_USER       => array('allow' => array('get', 'post', 'put', 'delete')),
        Tools_Security_Acl::ROLE_MEMBER     => array('allow' => array('get')),
        Tools_Security_Acl::ROLE_GUEST      => array('allow' => array('get'))
    );

    public function getAction() {
        // at first we will try to find content by id
        if(($containerId = intval(filter_var($this->_request->getParam('id'), FILTER_SANITIZE_NUMBER_INT)) ) == 0) {
            $containerId = filter_var($this->_request->getParam('name'), FILTER_SANITIZE_STRING);
        }
        $pageId    = $this->_request->getParam('pid', null);
        // return only content for the containers
        $contentOnly = $this->_request->getParam('co', false);
        $mapper      = Application_Model_Mappers_ContainerMapper::getInstance();
        $parser      = new Tools_Content_Parser(null, array(), array('websiteUrl' => Zend_Controller_Action_HelperBroker::getStaticHelper('website')->getUrl()));

        // querying all containers
        if(!$containerId) {
            $containers = $mapper->fetchAll();
            if(empty($containers)) {
                return $this->_error('404 Containers not found.', self::REST_STATUS_NOT_FOUND);
            }
            return array_map(function($container) use($contentOnly, $parser, $pageId) {
                $container = $container->toArray();
                $page      = ($pageId) ? Application_Model_Mappers_PageMapper::getInstance()->find($pageId) : null;

                $parser->setPageData(($page instanceof Application_Model_Models_Page) ? $page->toArray() : array());
                $container['content'] = $parser->setContent($container['content'])->parseSimple();
                return $contentOnly ? array($container['name'] => $container['content']) : $container;
            }, $containers);
        }

        $type      = $this->_request->getParam('type', Application_Model_Models_Container::TYPE_REGULARCONTENT);
        $pageId    = $this->_request->getParam('pid', null);
        if((integer)$type == Application_Model_Models_Container::TYPE_STATICCONTENT) {
            $pageId = null;
        }
        $container = is_integer($containerId) ? $mapper->find($containerId) : $mapper->findByName($containerId, $pageId, $type);
        $pageId = $this->_request->getParam('pid', null);
        if(!$container instanceof Application_Model_Models_Container) {
            $container = new Application_Model_Models_Container(array(
                'containerType' => $type,
                'name'          => $containerId
            ));
        } else {
            if(!$pageId) {
                $pageId = $container->getPageId();
            }
            $page = ($pageId) ? Application_Model_Mappers_PageMapper::getInstance()->find($pageId) : null;
            $parser->setPageData(($page instanceof Application_Model_Models_Page) ? $page->toArray() : array())
                ->setContent($container->getContent());
            $container->setContent($parser->parseSimple());
        }
        return ($contentOnly) ? array($container->getName() => $container->getContent()) : $container->toArray();
    }

    public function postAction() {}
    public function putAction() {}
    public function deleteAction() {}
}