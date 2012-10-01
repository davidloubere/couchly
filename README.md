# Couchly

Couchly is a simple CouchDB object-document mapper (ODM) written in PHP


## Dependencies

For now, Couchly relies on PHP 5.3 or later and some Zend Framework components.


## Installation

  - Symlink to Zend Framework library into Couchly library folder

    ```
    $ cd /PATH/TO/COUCHLY/library
    $ ln -s /PATH/TO/ZEND_FRAMEWORK/library/Zend Zend
    ```

  - Symlink to Couchly library into your application library folder

    ```
    $ cd /PATH/TO/YOUR_APP/LIB
    $ ln -s /PATH/TO/COUCHLY/library/Couchly Couchly
    ```

  - Enable the Couchly command line generator

    If not already done, make `couchly-gen.php` executable
    
    ```
    $ chmod +x /PATH/TO/COUCHLY/bin/couchly-gen.php
    ```
    
    Add an alias for the couchly-gen command (e.g. into your `.bashrc`)
    
    ```
    alias couchly-gen=/PATH/TO/COUCHLY/bin/couchly-gen.php
    ```


## Command line usage

  - Into directory `/PATH/TO/YOUR_APP/CONFIGS`, create a file named `schema.yml` describing your data

    ```
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
    ```

  - Again, into directory `/PATH/TO/YOUR_APP/CONFIGS`, create a file named `build.yml` defining the properties for build time

    ```
    dir:
      config: /PATH/TO/YOUR_APP/CONFIGS
      model: /PATH/TO/YOUR_APP/MODELS
    ```

    NB : Optionally, you can define specific properties for the model classes
    
    ```
    class:
      path: ./MODELS
      prefix: Your_App_
    ```
    
  - Run couchly-gen command

    ```
    $ couchly-gen /PATH/TO/YOUR_APP/CONFIGS/build.yml
    ```
    
## Initialize Couchly into your application

 - Now, to start using Couchly into your application, add the following code (e.g. into your `index.php`)
 
    ``` 
    // Include Couchly bootstrap class
    require_once('/PATH/TO/YOUR_APP/LIB/Couchly/Bootstrap.php');

    // Initialize Couchly
    Couchly_Bootstrap::init('/PATH/TO/YOUR_APP/CONFIGS/classmap.php');
    
    // Add the generated 'classes' directory to the include path
    set_include_path('/PATH/TO/YOUR_APP/MODELS' . PATH_SEPARATOR . get_include_path());
    
    // Initialize Couchly facades
    Couchly_Model_Mapper::initCouchlyFacades(array(
        Your_App_ModelNameA::MODEL_NAME => 'couchdb_database_a',
        Your_App_ModelNameB::MODEL_NAME => 'couchdb_database_b'
    ));
    ```