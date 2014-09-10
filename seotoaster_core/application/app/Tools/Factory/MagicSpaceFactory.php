<?php

/**
 * Magic spaces factory
 *
 * @author Eugene I. Nezhuta <eugene@seotoaster.com>
 * Date: 1/25/12
 * Time: 3:57 PM
 */
class Tools_Factory_MagicSpaceFactory
{
    private function  __construct()
    {

    }

    public static function createMagicSpace($name, $content, $toasterData, $params = array(), $magicLabel)
    {
        $name = ucfirst(strtolower($name));
        if (self::_validate($name)) {
            $magicSpaceClassName = 'MagicSpaces_'.$name.'_'.$name;
            return new $magicSpaceClassName($name, $content, $toasterData, $params, $magicLabel);
        }
        throw new Exceptions_SeotoasterException('Cannot run the magic space: '.$name);
    }

    private static function _validate($name)
    {
        $incPath = explode(PATH_SEPARATOR, get_include_path());
        if (is_array($incPath) && !empty($incPath)) {
            foreach ($incPath as $path) {
                $magicSpacesDir = $path.DIRECTORY_SEPARATOR.'MagicSpaces'.DIRECTORY_SEPARATOR.$name;
                if (is_dir($magicSpacesDir) && file_exists($magicSpacesDir.DIRECTORY_SEPARATOR.$name.'.php')) {
                    return true;
                }
            }
            return false;
        }
        return false;
    }
}
