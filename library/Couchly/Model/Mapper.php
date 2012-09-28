<?php
abstract class Couchly_Model_Mapper extends Couchly_Model_Abstract
{
    /**
     * @var Couchly_Facade
     */
    private static $_couchlyFacade = null;

    /**
     * @var string
     */
    private static $_dbName = null;

    /**
     * @var int
     */
    protected $_id = null;

    /**
     * @var string
     */
    protected $_rev = null;

    public function __constructor($id=null)
    {
        if (!is_null($id))
        {
            $doc = self::_getCouchlyFacade()->retrieve($id);
            if (is_null($doc))
            {
                throw new Couchly_Exception("Document not found (id: $id)");
            }
            $this->_populate($doc);
        }
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

    public function assignId($id)
    {
        $this->_id = $id;
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
                
                $calledClass = get_called_class();
                if (isset($docValue->type))
                {
                    $className = str_replace(
                        substr(strrchr($calledClass, '_'), 1),
                        Couchly_Generator::camelize($docValue->type, false),
                        $calledClass
                    );
                }
                else
                {
                    $className = $calledClass;
                }
                
                $object = new $className;
                $object->_populate($docValue);
                $collObjects[] = $object;
            }
        }

        return $collObjects;

    }

    public static function delete($id, $rev)
    {
        self::_getCouchlyFacade()->delete($id, $rev);
    }

    public static function initCouchlyFacade($dbName)
    {
        if (is_null(self::$_couchlyFacade))
        {
            self::$_couchlyFacade = new Couchly_Facade($dbName);
        }
        else
        {
            throw new Couchly_Exception("DB already initialized (db: $dbName)");
        }

    }

    protected static function _getCouchlyFacade()
    {
        return self::$_couchlyFacade;
    }
}