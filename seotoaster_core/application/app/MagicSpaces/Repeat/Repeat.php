<?php
/**
 * Repeats content with replacement tag on number of the current iteration
 */
class MagicSpaces_Repeat_Repeat extends Tools_MagicSpaces_Abstract
{
    const PREFIX_CONTAINER     = 'repeat_';

    protected $_popupWidth     = '480';

    protected $_popupHeighth   = '163';

    protected $_iterationLimit = 100;

    protected $_separatorOrder = ',';

    protected $_contentType    = Application_Model_Models_Container::TYPE_REGULARCONTENT;

    protected function _init()
    {
        $this->_qty     = 0;
        $this->_replace = '';
        $this->_order   = array();
    }

    /**
     * @return string
     */
    protected function _run()
    {
        if (!isset($this->_params[0], $this->_params[1])) {
            return $this->_spaceContent;
        }

        list($qty, $this->_replace) = $this->_params;

        if (!is_numeric($qty)) {
            $qty = 0;

            if (!empty($this->_params) && end($this->_params) === 'static') {
                $this->_contentType = Application_Model_Models_Container::TYPE_STATICCONTENT;
            }

            $data = Application_Model_Mappers_ContainerMapper::getInstance()->findByName(
                self::PREFIX_CONTAINER.$this->_params[0],
                $this->_toasterData['id'],
                $this->_contentType
            );

            if ($data instanceof Application_Model_Models_Container) {
                $content = explode(':', $data->getContent());
                if (isset($content[0]) && !empty($content[0])) {
                    $qty   = (int)$content[0];
                }
                if (isset($content[1]) && !empty($content[1])) {
                    $order = $content[1];
                }
            }
        }
        elseif (isset($this->_params[2]) && $this->_params[2] != 'static') {
            $order = $this->_params[2];
        }

        $this->_qty = ((int)$qty > $this->_iterationLimit) ? $this->_iterationLimit : $qty;
        if (isset($order)) {
            $this->_order = explode($this->_separatorOrder, preg_replace('/\s/', '', $order));
        }

        return $this->_getEditLink().$this->_getContent();
    }

    /**
     * Returns a link to edit
     *
     * @return string
     */
    private function _getEditLink()
    {
        $editLink = '';
        if (!is_numeric($this->_params[0]) && Tools_Security_Acl::isAllowed(Tools_Security_Acl::RESOURCE_CONTENT)) {
            $translator    = Zend_Registry::get('Zend_Translate');
            $editLink      = '<a class="tpopup generator-links" data-pwidth="'.$this->_popupWidth.'" data-pheight="'
                .$this->_popupHeighth.'" title="'.$translator->translate('Edit').'" href="javascript:;" data-url="'
                .$this->_toasterData['websiteUrl'].'backend/backend_content/editrepeat/pageId/'
                .$this->_toasterData['id'].'/repeatName/'.$this->_params[0].'/contentType/'.$this->_contentType.'">'
                .$translator->translate('Edit repeat') .'- <em>'.$this->_params[0].'</em>'
                .(($this->_contentType == Application_Model_Models_Container::TYPE_STATICCONTENT) ? ' (static)' : '')
                .'</a>';
        }

        return $editLink;
    }

    /**
     * Returns prepared content
     *
     * @return string
     */
    private function _getContent()
    {
        $orderContent = array();
        $content      = '';
        for ($i = 1; $i <= $this->_qty; $i++) {
            $val = str_replace($this->_replace, $i, $this->_spaceContent);

            if (!empty($this->_order) && (false !== ($key = array_search($i, $this->_order)))) {
                unset($this->_order[$key]);
                $orderContent[$key] = $val;
                continue;
            }
            $content .= $val;
        }

        if (!empty($orderContent)) {
            ksort($orderContent);
            $content = implode('', $orderContent).$content;
        }

        return $content;
    }
}
