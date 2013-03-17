<?php

namespace Couchly\Model;

abstract class AbstractMapper implements InterfaceMapper
{
    abstract protected function _populate(\stdClass $doc);

    abstract protected static function _getCouchLyFacade();
}
