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
    
    protected $_dirSchema = null;
    
    protected $_dirOutput = null;
    
    protected $_classnamePrefix = null;
    
    protected $_classProperties = array();
    
    protected $_output = array();
    
    public function __construct(Zend_Config_Yaml $configBuild)
    {
        try
        {
            if (isset($configBuild->dir->schema))
            {
                $this->_dirSchema = new Zend_Config_Yaml($configBuild->dir->schema);
            }
            else
            {
                throw new Couchly_Exception("Build requires the property dir:schema");
            }
            
            if (isset($configBuild->dir->output))
            {
                $this->_dirOutput = $configBuild->dir->output;
            }
            else
            {
                throw new Couchly_Exception("Build requires the property dir:output");
            }
            
            if (isset($configBuild->classname->prefix))
            {
                $this->_classnamePrefix = $configBuild->classname->prefix;
            }
            else
            {
                throw new Couchly_Exception("Build requires the property classname:prefix");
            }
            
            header('Content-Type: text/plain');
            
            $this->_generate();
            
            $this->_log('Done!');
        }
        catch(Couchly_Exception $ce)
        {
            die($ce->getMessage() . $this->_nl);
        }
    }
    
    protected function _write($modelName)
    {
        $file = $this->_dirOutput . '/' . $this->_camelize($modelName, false) . '.php';
        
        $this->_log('Writing file: ' . $file, false);
        
        file_put_contents(
            $file,
            implode($this->_nl, $this->_output)
        );
        
        unset($this->_output);
        unset($this->_classProperties);
        
        $this->_log($this->_tab . 'Ok');
    }
    
    protected function _log($trace, $nl=true)
    {
        echo $trace . ($nl ? $this->_nl : '');
    }
    
    protected function _generate()
    {
        foreach ($this->_dirSchema as $modelName => $modelDefinition)
        {
            $this->_log('Creating model: ' . $modelName, false);
            
            $className = $this->_classnamePrefix . $this->_camelize($modelName, false);
            
            /*
             * Class header
             */
            
            $this->_output[] = "<?php";
            $this->_output[] = "class $className extends Couchly_Model_Mapper";
            $this->_output[] = "{";


            /*
             * Constants
             */
            
            $this->_output[] = $this->_computeConstant('MODEL_NAME', "'" . $modelName . "'", array('Model name'));
            
            
            /*
             * Class properties
             */
            
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
            
            // Getters
            foreach ($this->_classProperties as $propertyName => $propertyDefinition)
            {
                if (!$propertyDefinition['is_static'])
                {
                    $this->_output[] = $this->_computeGetter($propertyName, $propertyDefinition['type'], $propertyDefinition['visibility']);
                }
            }
            
            // GetModelName()
            $content = $this->_tab . $this->_tab . "return self::MODEL_NAME;";
            $this->_output[] = $this->_computeMethod('getModelName', self::PHP_KW_PUBLIC, false, $content);
            
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
            
            
            /*
             * Class footer
             */
            
            $this->_output[] = "}";
            
            $this->_log($this->_tab . 'Ok');
            
            
            /*
             * Genrate model file
             */
            
            $this->_write($modelName);
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
    
    protected function _computeDocBlock(array $info)
    {
        $docBlock = array();
        $docBlock[] = $this->_tab . '/**';
        foreach ($info as $line)
        {
            $docBlock[] = $this->_tab . ' * ' . $line;
        }
        $docBlock[] = $this->_tab . ' */';
        return implode($this->_nl, $docBlock);
    }
    
    protected function _computeConstant($name, $value, array $docBlockInfo)
    {
        $constant = array();
        $constant[] = $this->_computeDocBlock($docBlockInfo);
        $constant[] = $this->_tab . 'const ' . $name . ' = ' . $value . ';';
        return implode($this->_nl, $constant) . $this->_nl;
    }
    
    protected function _computeProperty($name, $type, $visibility, $isStatic)
    {
        $property = array();
        $property[] = $this->_computeDocBlock(array('@var ' . $type));
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
        $getter[] = $this->_computeDocBlock(array('@return ' . $type));
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