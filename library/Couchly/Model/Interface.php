<?php
interface Couchly_Model_Interface
{
    public function isNew();

    public function getModelName();

    public function getId();

    public function getRev();

    public function save();

    public function toArray();

    public static function initCouchlyFacades(array $databaseMapping);

    public static function fetch(array $criteria=null);

    public static function findById($id);

    public static function delete($id, $rev);
}
