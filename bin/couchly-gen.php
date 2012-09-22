#!/usr/bin/php
<?php
// Include Couchly application bootstrap
require('../library/Couchly/bootstrap.php');

//
$buildProperties = array(
    'dir.schema' => '/var/projects/mmt-server/application/configs/couchly',
    'dir.output' => '/var/projects/mmt-server/application/models/couchly',
    'classname.prefix' => 'Application_Model_Couchly_'
);

//
$couchlyGenerator = new Couchly_Generator($buildProperties);
?>