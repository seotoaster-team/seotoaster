<?php
class MagicSpaces_Repeat_Repeat extends Tools_MagicSpaces_Abstract {

    protected function _run() {
        if (!isset($this->_params[0], $this->_params[1]) && !is_numeric($this->_params[0])) {
            return $this->_spaceContent;
        }

        list($qty, $replace) = $this->_params;

        $content = '';
        for ($i = 1; $i <= $qty; $i++) {
            $content .= str_replace($replace, $i, $this->_spaceContent);
        }

        return $content;
    }
}