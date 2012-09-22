<?php
/**
 * Demo facade
 * 
 * For this demo to work, you need to create a database: curl -X PUT http://127.0.0.1:5984/db_demo1
 */

// Include Couchly application bootstrap
require('../library/Couchly/bootstrap.php');

// Instantiate a Couchly facade
$couchlyFacade = new Couchly_Facade('db_demo1');

try
{
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
    echo "Document created (id=$id1)<br><br>";
    
    // Create a second document
    $id2 = uniqid();
    $couchlyFacade->save($id2, array(
        'firstname' => 'Foo',
        'lastname' => 'Bar',
        'created' => time()
    ));
    echo "Document created (id=$id2)<br><br>";

    
    /*
     * Fetch documents
     */
    
    echo "List of documents data<br>";
    $result = $couchlyFacade->fetch();
    if (isset($result->total_rows) && $result->total_rows > 0)
    {
        foreach ($result->rows as $document)
        {
            echo "<pre>".$document->id.' - '.$document->value->firstname.' '.$document->value->lastname."</pre>";
        }
    }
    echo "<br>";
    
    
    /*
     * Retrieve and delete documents
     */
    
    $ids = array($id1, $id2);
    foreach ($ids as $id)
    {
        // Retrieve the document
        $document = $couchlyFacade->retrieve($id);
        if (is_null($document))
        {
            echo "Document does not exist (id=$id)";
        }
        else
        {
            echo "Document retrieved (id=$id)";
            echo "<pre>";
            var_dump($document);
            echo "</pre>";
            
            // Delete the document
            $couchlyFacade->delete($id, $document->_rev);
            echo "Document deleted (id=$id)<br>";
        }
        echo "<br>";
    }
}
catch (Couchly_Exception $ce)
{
    die($ce->getMessage());
}
?>