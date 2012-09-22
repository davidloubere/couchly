<?php
class Couchly_Generator
{
    const PHP_KW_PUBLIC = 'public';
    const PHP_KW_PROTECTED = 'protected';
    const PHP_KW_PRIVATE = 'private';
    const PHP_KW_STATIC = 'static';
    const PHP_KW_FUNCTION = 'function';
    const PHP_KW_RETURN = 'return';
    
    protected $_nl = "\n";
    
    protected $_tab = '    ';
    
    protected $_schema = null;
    
    protected $_outputDir = null;
    
    protected $_classnamePrefix = null;
    
    protected $_classProperties = array();
    
    protected $_output = array();
    
    public function __construct(array $buildProperties)
    {
        if (array_key_exists('dir.schema', $buildProperties))
        {
            $this->_schema = new Zend_Config_Yaml($buildProperties['dir.schema'] . '/schema.yml');
        }
        
        if (array_key_exists('dir.output', $buildProperties))
        {
            $this->_outputDir = $buildProperties['dir.output'];
        }
        
        if (array_key_exists('classname.prefix', $buildProperties))
        {
            $this->_classnamePrefix = $buildProperties['classname.prefix'];
        }
        
        $this->_generate();
    }
    
    protected function _output($modelName)
    {
        header('Content-Type: text/plain');
    
        file_put_contents(
            $this->_outputDir . '/' . $this->_camelize($modelName, false) . '.php',
            implode($this->_nl, $this->_output)
        );
    
        unset($this->_output);
    }
    
    protected function _generate()
    {
        foreach ($this->_schema as $modelName => $modelDefinition)
        {
            $className = $this->_classnamePrefix . ucfirst($modelName);
            
            
            /*
             * Class header
             */
            
            $this->_output[] = "<?php";
            $this->_output[] = "class $className extends Couchly_Model_Abstract";
            $this->_output[] = "{";

            
            /*
             * Class properties
             */
            
            $this->_addClassProperty('couchly_facade', 'Couchly_Facade', self::PHP_KW_PRIVATE, true);
            $this->_addClassProperty('db_name', 'string', self::PHP_KW_PRIVATE, true);
            $this->_addClassProperty('id', 'int', self::PHP_KW_PROTECTED);
            $this->_addClassProperty('rev', 'string', self::PHP_KW_PROTECTED);
            foreach ($modelDefinition->fields as $fieldName => $fieldDefinition)
            {
                $this->_addClassProperty($fieldName, $fieldDefinition->type, self::PHP_KW_PROTECTED, false, true);
            }
            
            foreach ($this->_classProperties as $propertyName => $propertyDefinition)
            {
                $this->_output[] = $this->_computeProperty($propertyName, $propertyDefinition['type'], $propertyDefinition['visibility'], $propertyDefinition['is_static']);
            }
            
            
            /*
             * Class methods
             */
            
            // Contructor
            $content = $this->_tab . $this->_tab . 'if (!is_null($id))
        {
            $doc = self::_getCouchlyFacade()->retrieve($id);
            if (is_null($doc))
            {
                throw new Couchly_Exception("Document not found (id: $id)");
            }
            $this->_populate($doc);
        }';
            $this->_output[] = $this->_computeMethod('__constructor', self::PHP_KW_PUBLIC, false, $content, '$id=null');            
            
            // Getters
            foreach ($this->_classProperties as $propertyName => $propertyDefinition)
            {
                if (!$propertyDefinition['is_static'])
                {
                    $this->_output[] = $this->_computeGetter($propertyName, $propertyDefinition['type'], $propertyDefinition['visibility']);
                }
            }
            
            // GetModelType()
            $content = $this->_tab . $this->_tab . "return '$modelName';";
            $this->_output[] = $this->_computeMethod('getModelType', self::PHP_KW_PUBLIC, false, $content);
            
            // isNew()
            $content = $this->_tab . $this->_tab . 'return is_null($this->_rev)?true:false;';
            $this->_output[] = $this->_computeMethod('isNew', self::PHP_KW_PUBLIC, false, $content);
            
            // assignId()
            $content = $this->_tab . $this->_tab . '$this->_id = $id;';
            $this->_output[] = $this->_computeMethod('assignId', self::PHP_KW_PUBLIC, false, $content, '$id');
            
            // setData()
            $content = array();
            foreach ($this->_classProperties as $propertyName => $propertyDefinition)
            {
                if ($propertyDefinition['is_data'])
                {
                    $content[] = $this->_tab . $this->_tab . '$this->_' . self::_camelize($propertyName) . ' = $data[\'' . $propertyName . '\'];';
                }
            }
            $this->_output[] = $this->_computeMethod('setData', self::PHP_KW_PUBLIC, false, implode($this->_nl, $content), 'array $data');

            // save()
            $content = array();
            $content[] = $this->_tab . $this->_tab . "if (\$this->isNew())
        {
            // Create new document
            \$doc['_id'] = (string)\$this->_id;
        }
        else
        {
            // Update document
            \$doc['_rev'] = \$this->_rev;
        }";
            $content[] = $this->_tab . $this->_tab;
            foreach ($this->_classProperties as $propertyName => $propertyDefinition)
            {
                if ($propertyDefinition['is_data'])
                {
                    $content[] = $this->_tab . $this->_tab . "\$doc['data']['" . $propertyName . "'] = " . '$this->_' . self::_camelize($propertyName) . ';';
                }
            }
            $content[] = $this->_tab . $this->_tab;
            $content[] = $this->_tab . $this->_tab . 'self::_getCouchlyFacade()->save($this->_id, $doc);';
            $this->_output[] = $this->_computeMethod('save', self::PHP_KW_PUBLIC, false, implode($this->_nl, $content));
            
            // _getData()
            $content = array();
            $content[] = $this->_tab . $this->_tab . '$data = array(';
            $content[] = $this->_tab . $this->_tab . $this->_tab . "'id' => \$this->_id,";
            $content[] = $this->_tab . $this->_tab . $this->_tab . "'rev' => \$this->_rev,";
            foreach ($this->_classProperties as $propertyName => $propertyDefinition)
            {
                if ($propertyDefinition['is_data'])
                {
                    $content[] = $this->_tab . $this->_tab . $this->_tab . "'" . $propertyName . "' => \$this->_" . self::_camelize($propertyName) . ',';
                }
            }
            $content[] = $this->_tab . $this->_tab . ');';
            $content[] = $this->_tab . $this->_tab . 'return $data;';
            $this->_output[] = $this->_computeMethod('getData', self::PHP_KW_PROTECTED, false, implode($this->_nl, $content));

            // _populate()
            $content = array();
            $content[] = $this->_tab . $this->_tab . "\$this->_id = \$doc->_id;";
            $content[] = $this->_tab . $this->_tab . "\$this->_rev = \$doc->_rev;";
            foreach ($this->_classProperties as $propertyName => $propertyDefinition)
            {
                if ($propertyDefinition['is_data'])
                {
                    $content[] = $this->_tab . $this->_tab . "\$this->_" . $this->_camelize($propertyName) . " = \$doc->data->$propertyName;";
                }
            }
            $this->_output[] = $this->_computeMethod('populate', self::PHP_KW_PROTECTED, false, implode($this->_nl, $content), 'stdClass $doc');            
            
            // fetch()
            $content = array();
            $content[] = $this->_tab . $this->_tab . '$coll' . $this->_camelize($modelName, false) .'s = array();
    
        $result = self::_getCouchlyFacade()->fetch($criteria);
        if (isset($result->total_rows) && $result->total_rows > 0)
        {
            foreach ($result->rows as $doc)
            {
                $' . $modelName .' = new ' . $className . '();
                $' . $modelName .'->_populate($doc->value);
                $coll' . $this->_camelize($modelName, false) .'s[] = $' . $modelName .'->_getData();
            }
        }
    
        return $collAnnouncements;';
            $content[] = $this->_tab . $this->_tab;
            $this->_output[] = $this->_computeMethod('fetch', self::PHP_KW_PUBLIC, true, implode($this->_nl, $content), 'array $criteria=null');
            
            // delete()
            $content = $this->_tab . $this->_tab . 'self::_getCouchlyFacade()->delete($id, $rev);';
            $this->_output[] = $this->_computeMethod('delete', self::PHP_KW_PUBLIC, true, $content, '$id, $rev');
            
            // initCouchlyFacade()
            $content = array();
            $content[] = $this->_tab . $this->_tab . 'if (is_null(self::$_couchlyFacade))
        {
            self::$_couchlyFacade = new Couchly_Facade($dbName);
        }
        else
        {
            throw new Couchly_Exception("DB already initialized (db: $dbName)");
        }';
            $content[] = $this->_tab . $this->_tab;
            $this->_output[] = $this->_computeMethod('initCouchlyFacade', self::PHP_KW_PUBLIC, true, implode($this->_nl, $content), '$dbName');
            
            // _getCouchlyFacade()
            $content = $this->_tab . $this->_tab . 'return self::$_couchlyFacade;';
            $this->_output[] = $this->_computeMethod('getCouchlyFacade', self::PHP_KW_PROTECTED, true, $content);
            
            
            /*
             * Class footer
             */
            
            $this->_output[] = "}";
            
            
            /*
             * Genrate model file
             */
            
            $this->_output($modelName);
        }
    }
    
    protected function _addClassProperty($name, $type, $visibility, $isStatic=false, $isData=false)
    {
        $this->_classProperties[$name] = array(
            'type' => $type,
            'visibility' => $visibility,
            'is_static' => $isStatic,
            'is_data' => $isData
        );
    }
    
    protected function _computeProperty($name, $type, $visibility, $isStatic)
    {
        $property = array();
        $property[] = $this->_tab . '/**';
        $property[] = $this->_tab . ' * @var ' . $type;
        $property[] = $this->_tab . ' */';
        $property[] = $this->_tab . $visibility . ' ' . ($isStatic ? self::PHP_KW_STATIC . ' ' : '') . ($visibility === self::PHP_KW_PUBLIC ? '$' : '$_') . self::_camelize($name) . ' = null;';
        return implode($this->_nl, $property) . $this->_nl;
    }
    
    protected function _computeMethod($name, $visibility, $isStatic, $content, $args=null)
    {
        $method = array();
        $method[] = $this->_tab . $visibility . ' ' . ($isStatic ? self::PHP_KW_STATIC . ' ' : '') . self::PHP_KW_FUNCTION . ' ' . ($visibility === self::PHP_KW_PUBLIC ? '' : '_') . $name . '(' . (is_null($args) ? '' : $args) . ')';
        $method[] = $this->_tab . '{';
        $method[] = $content;
        $method[] = $this->_tab . '}';
        return implode($this->_nl, $method) . $this->_nl;
    }
    
    protected function _computeGetter($name, $type, $visibility)
    {
        $getter = array();
        $getter[] = $this->_tab . '/**';
        $getter[] = $this->_tab . ' * @return ' . $type;
        $getter[] = $this->_tab . ' */';
        $getter[] = $this->_tab . self::PHP_KW_PUBLIC . ' ' . self::PHP_KW_FUNCTION . ' get' . self::_camelize($name, false) . '()';
        $getter[] = $this->_tab . '{';
        $getter[] = $this->_tab . $this->_tab . self::PHP_KW_RETURN . ' $this->' . ($visibility === self::PHP_KW_PUBLIC ? '' : '_') . self::_camelize($name) . ';';
        $getter[] = $this->_tab . '}';
        return implode($this->_nl, $getter) . $this->_nl;
    }

    protected static function _camelize($value, $lcfirst=true)
    {
        $value = preg_replace("/([_-\s]?([a-z0-9]+))/e", "ucwords('\\2')", $value);
        return ($lcfirst ? strtolower($value[0]) : strtoupper($value[0])) . substr($value, 1);
    }
}