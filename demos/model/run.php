<?php

use Couchly\Bootstrap;
use Couchly\Model\Mapper;

/**
 * Demo "model"
 *
 * This demo works with a local CouchDB store named "db_demo_couchly"
 * You can create it running the following command: curl -X PUT http://127.0.0.1:5984/db_demo_couchly
 */

header('Content-type: text/plain');

// Include Couchly bootstrap class
require_once(dirname(realpath(__FILE__)) . '/../../library/Couchly/Bootstrap.php');

try {
    /*
     * Generate model
     *
     * NB: this step should be done using CLI (here for demo purpose only)
     */

    chdir(dirname(realpath(__FILE__)));
    exec('../../bin/couchly-gen.php config/build.yml 2>&1', $output);
    foreach ($output as $line) echo $line . "\n";


    /*
     * Init
     */

    // Initialize Couchly
    Bootstrap::init('config/classmap.php');

    // Define Facade/DB mapping
    Mapper::initCouchlyFacades(array(
        \CouchlyDemo\Blog\Entry::FACADE_MODEL_NAME => 'db_demo_couchly'
    ));


    /*
     * Create article
     */

    $article = new \CouchlyDemo\Blog\Article();
    $article->setId(uniqid());
    $article->setCreated(time());
    $article->setCreator("Jimi Hendrix");
    $article->setContent("
Queen jealousy, envy waits behind him.\nHer fiery green gown sneers at the grassy ground.\nBlue are the life giving waters taking for granted,\nThey quietly understand.\n\n
Once happy turquoise armies lay opposite ready,\nBut wonder why the fight is on.\nBut they're all, bold as love.\nYeah, they're all bold as love.\nYeah, they're all bold as love.\nJust ask the Axis.\n\n
My red is so confident he flashes trophies of war\nAnd ribbons of euphoria.\nOrange is young, full of daring but very unsteady for the first go 'round.\nMy yellow in this case is no so mellow.\nIn fact I'm trying to say it's frightened like me.\nAnd all of these emotions of mine keep holding me\nFrom giving my life to a rainbow like you.\nBut I'm a yeah, I'm bold as love,\nYeah yeah.\n\n
Well, I'm bold, bold as love.\nHear me talkin', girl.\nI'm bold as love.\nJust ask the Axis.\nHe knows everything. Yeah, yeah.\n\n
    ");
    $article->setPermalink('/blog/bold-as-love');
    $article->setTitle('Bold as love');
    $article->save();


    /*
     * Retrieve and delete entries
     */

    $collEntry = \CouchlyDemo\Blog\Entry::fetch();
    foreach ($collEntry as $entry) {
        $id = $entry->getId();
        $rev = $entry->getRev();

        echo "Document retrieved (id=$id)";
        print_r($entry);

        // Delete the document
        \CouchlyDemo\Blog\Entry::delete($id, $rev);
        echo "Document deleted (id=$id, rev=$rev)\n";
    }
}
catch (\Exception $e) {
    die($e->getMessage());
}
?>