Couchly
=======

Couchly is a simple CouchDB object-document mapper (ODM) written in PHP


Dependencies
------------

For now, Couchly relies on PHP 5.3 or later and some Zend Framework components.


Installation
------------

1. Symlink to Couchly and Zend librairies into your application library folder

  cd /PATH/TO/YOUR_APP/LIB
  ln -s /PATH/TO/COUCHLY/library/Couchly Couchly
  ln -s /PATH/TO/ZEND_FRAMEWORK/library/Zend Zend

2. Define the path to your application library folder into /PATH/TO/COUCHLY/bootstrap.php

  // Define path to library directory
  define('LIBRARY_PATH', '/PATH/TO/YOUR_APP/LIB');

3. Enable the Couchly command line generator

  3.1 Make couchly-gen.php executable
  
    chmod +x /PATH/TO/COUCHLY/bin/couchly-gen.php
   
  3.2 Add an alias for the couchly-gen command (e.g. into your .bashrc)
  
    alias couchly-gen=/PATH/TO/COUCHLY/bin/couchly-gen.php

4. To initialize Couchly into your application, you need to include the bootstrap (e.g. into your index.php)

  require('/PATH/TO/COUCHLY/bootstrap.php');
  

Command line usage
------------------

1. Create a file named 'schema.yml' describing your data as follow

  model_name_a:
    fields:
      field_name_a1:
        type: int
      field_name_a2:
        type: string
  model_name_b:
    fields:
      field_name_b1:
        type: int
      field_name_b2:
        type: string

2. Create a file named 'build.yml' defining the properties for build time

  dir:
    schema: /PATH/TO/YOUR_APP/CONFIGS/schema.yml
    output: /PATH/TO/YOUR_APP/MODELS/
  classname:
    prefix: Your_App_
    
3. Run couchly-gen