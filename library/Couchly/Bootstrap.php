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
        if (!is_null($classmapFile)) {
            // Retrieve and assign classmap
            self::$_classmap = include($classmapFile);
        }

        // Define path to Couchly library directory
        define('COUCHLY_LIBRARY_PATH', dirname(realpath(__FILE__)) . '/..');

        // Ensure the library is on include_path
        set_include_path(implode(PATH_SEPARATOR, array(
            COUCHLY_LIBRARY_PATH, get_include_path()
        )));

        // Add composer autoload
        require(COUCHLY_LIBRARY_PATH . '/../vendor/autoload.php');

        // Register autoload
        spl_autoload_register('\Couchly\Bootstrap::_autoload');
    }

    /**
     * Couchly classes autoloader
     *
     * @param string $className
     */
    private static function _autoload($className)
    {
        if (preg_match('/^Couchly\\\\/', $className)) {
            // Load Couchly library classes using include_path
            require_once(str_replace('\\', '/', $className . '.php'));
        }
        elseif (!is_null(self::$_classmap) && array_key_exists($className, self::$_classmap)) {
            // Load user-defined model classes using generated classmap
            $file = self::$_classmap[$className];
            if (is_readable($file)) {
                require_once($file);
            }
        }
    }
}
?>
