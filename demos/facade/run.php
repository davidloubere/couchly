<?php

use Couchly\Bootstrap;
use Couchly\Facade;

/**
 * Demo "Facade"
 *
 * This demo works with a local CouchDB store named "db_demo_couchly"
 * You can create it running the following command: curl -X PUT http://127.0.0.1:5984/db_demo_couchly
 */

header('Content-type: text/plain');

// Include Couchly bootstrap class
require_once(dirname(realpath(__FILE__)) . '/../../library/Couchly/Bootstrap.php');

try {
    /*
     * Init
     */

    // Initialize Couchly
    Bootstrap::init();

    // Instantiate a Couchly facade
    $couchlyFacade = new Facade('db_demo_couchly');


    /*
     * Create documents
     */
    
    // Create a first document
    $id1 = uniqid();
    $couchlyFacade->save($id1, array(
        'firstname' => 'John',
        'lastname' => 'Doe',
        'created' => time()
    ));
    echo "Document created (id=$id1)\n\n";
    
    // Create a second document
    $id2 = uniqid();
    $couchlyFacade->save($id2, array(
        'firstname' => 'Foo',
        'lastname' => 'Bar',
        'created' => time()
    ));
    echo "Document created (id=$id2)\n\n";

    
    /*
     * Fetch documents
     */
    
    echo "List of documents data\n";
    $result = $couchlyFacade->fetch();
    if (isset($result->total_rows) && $result->total_rows > 0) {
        foreach ($result->rows as $document) {
            echo $document->id . ' - ' . $document->value->firstname . ' ' . $document->value->lastname;
        }
    }
    echo "\n";
    
    
    /*
     * Retrieve and delete documents
     */
    
    $ids = array($id1, $id2);
    foreach ($ids as $id) {
        // Retrieve the document
        $document = $couchlyFacade->retrieve($id);
        if (is_null($document)) {
            echo "Document does not exist (id=$id)";
        }
        else {
            echo "Document retrieved (id=$id)";
            print_r($document);
            
            // Delete the document
            $couchlyFacade->delete($id, $document->_rev);
            echo "Document deleted (id=$id)\n";
        }
        echo "\n";
    }
}
catch (\Exception $e) {
    die($e->getMessage());
}
?>