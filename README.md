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

    Make `couchly-gen.php` executable
    
    ```
    $ chmod +x /PATH/TO/COUCHLY/bin/couchly-gen.php
    ```
    
    Add an alias for the couchly-gen command (e.g. into your `.bashrc`)
    
    ```
    alias couchly-gen=/PATH/TO/COUCHLY/bin/couchly-gen.php
    ```

  - Initialize Couchly into your application (e.g. into your `index.php`)

    ```
    // Include Couchly bootstrap class
    require_once('/PATH/TO/YOUR_APP/LIB/Couchly/Bootstrap.php');

    // Initialize Couchly
    Couchly_Bootstrap::init();
    ```

## Command line usage

  - Create a file named `schema.yml` describing your data as follow

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

  - Create a file named `build.yml` defining the properties for build time

    ```
    dir:
      schema: /PATH/TO/YOUR_APP/CONFIGS/schema.yml
      output: /PATH/TO/YOUR_APP/MODELS/
    classname:
      prefix: Your_App_
    ```

  - Run couchly-gen command

    ```
    $ couchly-gen /PATH/TO/YOUR_APP/CONFIGS/build.yml
    ```