#!/usr/bin/php
<?php
if ($argc != 2 || in_array($argv[1], array('--help', '-help', '-h', '-?')))
{
    echo "usage: couchly-gen <PATH/TO/YOUR_APP/CONFIGS/build.yml>\n";
}
else
{
    // Include Couchly bootstrap class
    require_once(dirname(realpath(__FILE__)) . '/../library/Couchly/Bootstrap.php');

    // Initialize Couchly
    Couchly_Bootstrap::init();

    // Retrieve the build properties
    if (file_exists($argv[1]))
    {
        $configBuild = new Zend_Config_Yaml($argv[1]);
        if (!$configBuild->valid())
        {
            echo ("Couchly error: build configuration file not valid '$argv[1]'.\n");
        }
        else
        {
            // Instantiate the generator
            $couchlyGenerator = new Couchly_Generator($configBuild);
        }
    }
    else
    {
        die("Couchly error: build configuration file not found '$argv[1]'.\n");
    }
}
?>