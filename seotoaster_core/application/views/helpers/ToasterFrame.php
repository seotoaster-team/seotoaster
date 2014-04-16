<?php
/**
 * Toaster frame helper. Create iframe to the main seotoaster website
 *
 */
class Zend_View_Helper_ToasterFrame extends Zend_View_Helper_Abstract {

    const REMOTE_URL = 'www.seotoaster.com/';

    public function toasterFrame($pageUrl = 'index.html', $params = array()) {
        //defaults
        $params = $this->_initDefaults($params);
        return '<iframe
                    src="//' . self::REMOTE_URL . $pageUrl . '"
                    width="' . $params['width'] . '"
                    height="' . $params['height'] . '"
                    frameborder="' . intval($params['frameborder']) . '"
                    seamless scrolling="auto">
                </iframe>';
    }

    private function _initDefaults($params) {
        return array(
            'width'       => isset($params['width']) ? $params['width'] : '100%',
            'height'      => isset($params['height']) ? $params['height'] : '100%',
            'frameborder' => isset($params['frameborder']) ? $params['frameborder'] : 'no',
        );
    }
}