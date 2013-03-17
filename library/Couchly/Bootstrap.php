<?php

namespace Couchly;

class Bootstrap
{
    private static $_classmap = null;

    /**
     * Couchly initialization
     */
    public static function init($classmapFile=null)
    {
        if (!is_null($classmapFile))
        {
            // Retrieve and assign classmap
            self::$_classmap = include($classmapFile);
        }

        // Define path to Couchly library directory
        define('COUCHLY_LIBRARY_PATH', dirname(realpath(__FILE__)) . '/..');

        // Ensure the library is on include_path
        set_include_path(implode(PATH_SEPARATOR, array(
            COUCHLY_LIBRARY_PATH, get_include_path()
        )));

        // Adds composer autoload
        require(COUCHLY_LIBRARY_PATH . '/../vendor/autoload.php');

        // Register autoload
        spl_autoload_register('\Couchly\Bootstrap::_autoload');
    }

    /**
     * Couchly class autoloader
     *
     * @param string $className
     */
    private static function _autoload($className)
    {
        require_once(str_replace('\\', '/', $className . '.php'));
    }
}
?>
