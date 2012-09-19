<?php
interface Couchly_Interface
{
    public function isNew();

    public function getModelType();

    public function getId();
    
    public function getRev();

    public function setData(array $data);

    public function assignId($id);

    public function save();

    public static function initCouchlyFacade($dbName);

    public static function fetch(array $criteria=null);
    
    public static function delete($id, $rev);
}
