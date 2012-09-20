<?php
abstract class Couchly_Model_Abstract implements Couchly_Model_Interface
{
    abstract protected function _populate(stdClass $doc);

    abstract protected function _getData();

    abstract protected static function _getCouchLyFacade();
}
