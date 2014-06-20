<?php
/**
 * Repeats content with replacement tag on number of the current iteration
 */
class MagicSpaces_Repeat_Repeat extends Tools_MagicSpaces_Abstract
{
    const PREFIX_CONTAINER     = 'repeat_';

    protected $_popupWidth     = '480';

    protected $_popupHeighth   = '170';

    protected $_iterationLimit = 100;

    protected $_separatorOrder = ',';

    /**
     * @return string
     */
    protected function _run()
    {
        if (!isset($this->_params[0], $this->_params[1])) {
            return $this->_spaceContent;
        }

        list($qty, $replace) = $this->_params;
        if (isset($this->_params[2])) {
            $order = ($this->_params[2]);
        }

        if (!is_numeric($qty)) {
            $qty  = 0;
            $data = Application_Model_Mappers_ContainerMapper::getInstance()->findByName(
                self::PREFIX_CONTAINER.$this->_params[0],
                $this->_toasterData['id'],
                Application_Model_Models_Container::TYPE_REGULARCONTENT
            );
            if ($data instanceof Application_Model_Models_Container) {
                $content = explode(':', $data->getContent());
                if (isset($content[0]) && !empty($content[0])) {
                    $qty   = (int) $content[0];
                }
                if (isset($content[1]) && !empty($content[1])) {
                    $order = $content[1];
                }
            }
        };

        if ((int) $qty > $this->_iterationLimit) {
            $qty = $this->_iterationLimit;
        }
        if (isset($order)) {
            $order = explode($this->_separatorOrder, preg_replace('/\s/', '', $order));
        }

        $orderContent = array();
        $content      = '';
        for ($i = 1; $i <= $qty; $i++) {
            $val = str_replace($replace, $i, $this->_spaceContent);

            if (isset($order) && (false !== ($key = array_search($i, $order)))) {
                $orderContent[$key] = $val;
                continue;
            }
            $content .= $val;
        }

        if (!empty($orderContent)) {
            ksort($orderContent);
            $content = implode('', $orderContent).$content;
        }

        return $this->_getEditLink().$content;
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
                .$this->_toasterData['id'].'/repeatName/'.$this->_params[0].'">'.$translator->translate('Edit repeat')
                .'</a>';
        }

        return $editLink;
    }
}
