<?php 
// Define path to library directory
define('LIBRARY_PATH', '/var/projects/library');

// Ensure libraries are on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    LIBRARY_PATH, get_include_path()
)));

// Couchly autoloading
function __autoload($className)
{
    if (preg_match('/^Couchly|^Zend/', $className))
    {
        $root = LIBRARY_PATH;
    }
    else
    {
        die("Couchly autoloader error: namespace not defined for '$className'.");
    }
    
    $file = $root . '/' . implode('/', explode('_', $className)) . '.php';
    if (file_exists($file))
    {
        require_once($file);
    }
    else
    {
        die("Couchly autoloader error: file not found '$className'.");
    }
}
?>