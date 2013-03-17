<?php

namespace Couchly\Model;

use Couchly\Exception\MapperException;
use Couchly\Facade;
use Couchly\Utils;

abstract class Mapper extends AbstractMapper
{
    /**
     * @var array
     */
    private static $_collCouchlyFacades = array();

    /**
     * @var int
     */
    protected $_id = null;

    /**
     * @var string
     */
    protected $_rev = null;

    public function __construct($id=null)
    {
        if (!is_null($id))
        {
            $doc = self::_getCouchlyFacade()->retrieve($id);
            if (is_null($doc))
            {
                throw new MapperException("Document not found (id: $id)");
            }
            $this->_populate($doc);
        }
    }

    /**
     * @param int
     */
    public function setId($id)
    {
        $this->_id = $id;
    }
    
    /**
     * @return int
     */
    public function getId()
    {
        return $this->_id;
    }
    
    /**
     * @return string
     */
    public function getRev()
    {
        return $this->_rev;
    }

    public function isNew()
    {
        return is_null($this->_rev)?true:false;
    }

    public function toArray()
    {
        $arr = array();
        
        $vars = get_object_vars($this);
        foreach ($vars as $k => $v)
        {
            $k = preg_replace('/^_/', '', Utils::decamelize($k));
            $arr[$k] = $v;
        }
        
        return $arr;
    }
    
    public static function fetch(array $criteria=null)
    {
        $collObjects = array();

        $result = self::_getCouchlyFacade()->fetch($criteria);
        if (isset($result->total_rows) && $result->total_rows > 0)
        {
            foreach ($result->rows as $doc)
            {
                $docValue = $doc->value;
                
                $className = get_called_class();
                
                $object = null;
                
                if (method_exists($className, '_getChildMap'))
                {
                    $childs = $className::_getChildMap();
                    foreach ($childs as $childModelName => $childClassName)
                    {
                        if (array_key_exists($childModelName, $docValue->data))
                        {
                            $object = new $childClassName();
                            $object->_populate($docValue);
                            break;
                        }
                    }
                }
                else
                {
                    $object = new $className();
                    $object->_populate($docValue);
                }
                
                if (is_null($object))
                {
                    throw new MapperException("Unable to determine model object for document (id: $docValue->_id, rev: $docValue->_rev)");
                }
                
                $collObjects[] = $object;
            }
        }

        return $collObjects;
    }

    public static function findById($id)
    {
        $object = null;
        
        $criteria = array(
            'condition' => array(
                array('_id', "'$id'", '===')
            )
        );
        $collObjects = self::fetch($criteria);
        if (!empty($collObjects) && count($collObjects) === 1)
        {
            $object = $collObjects[0];
        }
        
        return $object;
    }
    
    public static function delete($id, $rev)
    {
        self::_getCouchlyFacade()->delete($id, $rev);
    }

    public static function initCouchlyFacades(array $databaseMapping)
    {
        if (empty(self::$_collCouchlyFacades))
        {
            foreach ($databaseMapping as $modelName => $dbName)
            {
                self::$_collCouchlyFacades[$modelName] = new Facade($dbName);
            }
        }
        else
        {
            throw new MapperException("Facades already initialized");
        }
    }

    protected static function _getCouchlyFacade()
    {
        $couhlyFacade = null;
        
        $calledClass = get_called_class();
        $modelName = $calledClass::FACADE_MODEL_NAME;
        if (array_key_exists($modelName, self::$_collCouchlyFacades))
        {
            $couhlyFacade = self::$_collCouchlyFacades[$modelName];
        }
        else
        {
            throw new MapperException("Unable to retrieve facade for model '$modelName'");
        }

        return $couhlyFacade;
    }
}