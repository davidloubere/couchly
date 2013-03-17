#!/usr/bin/php
<?php

use Couchly\Bootstrap;
use Couchly\Generator;

if ($argc != 2 || in_array($argv[1], array('--help', '-h', '-?')))
{
    echo "usage: couchly-gen <PATH/TO/YOUR_APP/CONFIGS/build.yml>\n";
}
else
{
    // Include Couchly bootstrap class
    require_once(dirname(realpath(__FILE__)) . '/../library/Couchly/Bootstrap.php');

    // Initialize Couchly
    Bootstrap::init();

    // Retrieve the build properties
    if (basename($argv[1]) == 'build.yml' && file_exists($argv[1]))
    {
        // Instantiate the generator
        $couchlyGenerator = new Generator($argv[1]);
    }
    else
    {
        die("Couchly error: build configuration file not found '$argv[1]'.\n");
    }
}
?>