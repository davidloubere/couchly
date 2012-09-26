<?php
class Couchly_Bootstrap
{
    /**
     * Couchly initialization
     */
    public static function init()
    {
        // Define path to Couchly library directory
        define('COUCHLY_LIBRARY_PATH', dirname(realpath(__FILE__)) . '/..');
        
        // Ensure the library is on include_path
        set_include_path(implode(PATH_SEPARATOR, array(
            COUCHLY_LIBRARY_PATH, get_include_path()
        )));
        
        // Register autoload
        spl_autoload_register('Couchly_Bootstrap::_loader');
    }
    
    /**
     * Couchly class loader
     * 
     * @param string $className
     */
    private static function _loader($className)
    {
        if (preg_match('/^Couchly|^Zend/', $className))
        {
            require_once(str_replace('_', '/', $className . '.php'));
        }
    }
}
?>