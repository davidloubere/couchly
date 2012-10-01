<?php
abstract class Couchly_Model_Mapper extends Couchly_Model_Abstract
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
                throw new Couchly_Exception("Document not found (id: $id)");
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
                
                $object = new $className($docValue->_id);
                $collObjects[] = $object;
            }
        }

        return $collObjects;

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
                self::$_collCouchlyFacades[$modelName] = new Couchly_Facade($dbName);
            }
        }
        else
        {
            throw new Couchly_Exception("Facades already initialized");
        }
    }

    protected static function _getCouchlyFacade()
    {
        $calledClass = get_called_class();
        $modelName = $calledClass::MODEL_NAME;
        return self::$_collCouchlyFacades[$modelName];
    }
}