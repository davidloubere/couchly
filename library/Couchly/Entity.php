<?php
abstract class Couchly_Entity
{
    abstract protected function _populate(stdClass $doc);

    abstract protected function _getData();

    abstract protected static function _getCouchLyFacade();
}
