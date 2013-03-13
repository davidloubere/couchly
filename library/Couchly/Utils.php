<?php

namespace Couchly;

class Utils
{
    public static function camelize($value, $lcfirst=true)
    {
        $value = preg_replace("/([_-\s]?([a-z0-9]+))/e", "ucwords('\\2')", $value);
        return ($lcfirst ? strtolower($value[0]) : strtoupper($value[0])) . substr($value, 1);
    }
    
    public static function decamelize($value)
    {
        $separated = preg_replace('%(?<!^)\p{Lu}%usD', '_$0', $value);
        return strtolower($separated);
    }
}
