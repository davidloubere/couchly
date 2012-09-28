<?php
class Couchly_Bootstrap
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
        
        // Register autoload
        spl_autoload_register('Couchly_Bootstrap::_autoload');
    }

    /**
     * Couchly class autoloader
     * 
     * @param string $className
     */
    private static function _autoload($className)
    {
        if (preg_match('/^Couchly|^Zend/', $className))
        {
            // Autoloading through symlink
            require_once(str_replace('_', '/', $className . '.php'));
        }
        elseif (!is_null(self::$_classmap) && array_key_exists($className, self::$_classmap))
        {
            // Autoloading through classmap
            $includePath = explode(PATH_SEPARATOR, get_include_path());
            if (!empty($includePath))
            {
                foreach ($includePath as $path)
                {
                    $file = $path . '/' . self::$_classmap[$className];
                    if (is_readable($file))
                    {
                        require_once($file);
                        break;
                    }
                }
            }
        }
    }
}
?>
