<?php
/**
 * TODO:
 * - createDB(), deleteDB()
 * - compact
 */
class Couchly_Facade
{
    /**
     * @var Couchly_Client
     */
    protected $_couchlyClient = null;

    public function __construct($dbName)
    {
        $this->_couchlyClient = new Couchly_Client($dbName);
    }

    public function retrieve($id)
    {
        return $this->_couchlyClient->get($id);
    }

    public function fetch(array $criteria=null)
    {
        $sVar = '';
        $sCondition = '';
        $sKey = 'doc._id';
        
        if (!is_null($criteria))
        {
            // Variables definition
            if (array_key_exists('var', $criteria))
            {
                $aVar = array();
                foreach ($criteria['var'] as $name => $value)
                {
                    $aVar[] = "doc.$name = $value;";
                }
                $sVar = implode($aVar);
            }
            
            // Fetch condition
            if (array_key_exists('condition', $criteria))
            {
                $aCondition = array();
                foreach ($criteria['condition'] as $condition)
                {
                    $left = $condition[0];
                    $right = $condition[1];
                    $operator = isset($condition[2])?$condition[2]:'==';
                    $aCondition[] = "doc.$left $operator $right";
                }
                $sCondition = implode(' && ', $aCondition);
            }
            
            // Fetch key
            if (array_key_exists('key', $criteria))
            {
                $aKey = array();
                foreach ($criteria['key'] as $key)
                {
                    $aKey[] = "doc.$key"; 
                }
                $sKey = '['.implode(', ', $aKey).']';
            }
        }
        
        if (isset($aCondition) && !empty($aCondition))
        {
            $map = 'function(doc) {if ('.$sCondition.') {'.$sVar.'emit('.$sKey.', doc);}}';
        }
        else
        {
            $map = 'function(doc) {'.$sVar.'emit('.$sKey.', doc);}';
        }
        
        $viewDefinition = array('map' => $map);
        return $this->_couchlyClient->post($viewDefinition, '_temp_view?descending=true');
    }

    public function save($id, $doc)
    {
        $this->_couchlyClient->put($id, $doc);
    }

    public function delete($id, $rev)
    {
        $this->_couchlyClient->delete($id, $rev);
    }
}
