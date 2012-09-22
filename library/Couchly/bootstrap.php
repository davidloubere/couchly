<?php 
// Display errors
ini_set('display_errors', 1);

// Define path to library directory
define('ZEND_PATH', '/var/projects/library/vendor/zendframework/zendframework1/library/Zend/..');

// Define path to library directory
define('COUCHLY_PATH', realpath(dirname(__FILE__)) . '/..');

// Ensure libraries are on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    ZEND_PATH, COUCHLY_PATH, get_include_path()
)));

// Couchly autoloading
function __autoload($className)
{
    if (preg_match('/^Couchly/', $className))
    {
        $root = COUCHLY_PATH;
    }
    elseif (preg_match('/^Zend/', $className))
    {
        $root = ZEND_PATH;
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