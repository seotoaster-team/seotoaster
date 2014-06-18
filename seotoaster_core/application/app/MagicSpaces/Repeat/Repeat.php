<?php
/**
 * Repeats content with replacement tag on number of the current iteration
 */
class MagicSpaces_Repeat_Repeat extends Tools_MagicSpaces_Abstract
{
    protected $_separatorOrder = ',';

    protected function _run()
    {
        if (!isset($this->_params[0], $this->_params[1]) && !is_numeric($this->_params[0])) {
            return $this->_spaceContent;
        }

        list($qty, $replace) = $this->_params;
        $orderContent        = array();
        $content             = '';
        if (isset($this->_params[2])) {
            $order = explode($this->_separatorOrder, preg_replace('/\s/', '', $this->_params[2]));
        }

        for ($i = 1; $i <= $qty; $i++) {
            if (isset($this->_params[2]) && (false !== ($key = array_search($i, $order)))) {
                $orderContent[$key] = str_replace($replace, $i, $this->_spaceContent);
                continue;
            }
            $content .= str_replace($replace, $i, $this->_spaceContent);
        }

        if (!empty($orderContent)) {
            ksort($orderContent);
            $content = implode('', $orderContent).$content;
        }

        return $content;
    }
}
