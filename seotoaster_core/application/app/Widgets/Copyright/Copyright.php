<?php
/**
 * Returns copyright symbol with a current year
 */

class Widgets_Copyright_Copyright extends Widgets_Abstract
{

    const COPYRIGHT_SYMBOL = '©';

    protected function _init()
    {
        parent::_init();
    }

    protected function _load()
    {
        $currentYear = Tools_System_Tools::convertDateFromTimezone('now', false, 'UTC', 'Y');
        return self::COPYRIGHT_SYMBOL . ' ' . $currentYear;
    }
}


