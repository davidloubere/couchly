<?php

namespace Couchly;

use Couchly\Exception;
use Couchly\Utils;
use Symfony\Component\Yaml\Yaml;

class Generator
{
    const PHP_KW_PUBLIC = 'public';
    const PHP_KW_PROTECTED = 'protected';
    const PHP_KW_PRIVATE = 'private';
    const PHP_KW_STATIC = 'static';
    const PHP_KW_FUNCTION = 'function';
    const PHP_KW_RETURN = 'return';
    const PHP_KW_STDCLASS = 'stdClass';
    
    protected $_nl = "\n";
    
    protected $_tab = '    ';
    
    protected $_dirConfig = null;
    
    protected $_dirModel = null;
    
    protected $_schema = null;
    
    protected $_classPath = '';
    
    protected $_classPrefix = '';

    protected $_classNamespace = '';
    
    protected $_classProperties = array();
    
    protected $_classMap = array();
    
    protected $_childMap = array();
    
    protected $_output = array();
    
    public function __construct($configBuildFile)
    {
        try
        {
            // Parse build config from YAML file
            $configBuild = Yaml::parse($configBuildFile);

            // Assign mandatory properties
            $dir = Utils::getParam($configBuild, 'dir', true);
            $this->_dirConfig = Utils::getParam($dir, 'config', true);
            $this->_dirModel = Utils::getParam($dir, 'model', true);

            // Assign optional properties
            if (array_key_exists('class', $configBuild)) {
                $class = Utils::getParam($configBuild, 'class');
                $this->_classPath = Utils::getParam($class, 'path') ?: '';
                $this->_classPrefix = Utils::getParam($class, 'prefix') ?: '';
                $this->_classNamespace = Utils::getParam($class, 'namespace') ?: '';
            }

            // Assign schema
            $this->_schema = Yaml::parse($this->_dirConfig . '/schema.yml');
            
            // Main process
            header('Content-Type: text/plain');
            $this->_prepare();
            $this->_generate();
            $this->_log('Done!');
        }
        catch(Exception $ce)
        {
            die($ce->getMessage() . $this->_nl);
        }
    }
    
    protected function _writeModel($modelName)
    {
        $fileName = Utils::camelize($modelName, false) . '.php';
        $className = $this->_computeClassName($modelName);
        
        $this->_classMap[$className] = empty($this->_classPath) ? $fileName : $this->_classPath . '/' . $fileName;
        
        $file = $this->_dirModel . '/' . $fileName;
        $this->_log('Writing model file: ' . $file, false);
        
        file_put_contents(
            $file,
            implode($this->_nl, $this->_output)
        );
        
        unset($this->_output);
        unset($this->_classProperties);
        
        $this->_log($this->_tab . 'Ok');
    }
    
    protected function _writeClassmap()
    {
        $this->_log('Creating classmap: ', false);
        
        $file = $this->_dirConfig . '/classmap.php';
        
        $arrayLines = array();
        foreach ($this->_classMap as $className => $classPath) {
            $arrayLines[] = $this->_tab . "'" . $className . "' => '" . $classPath . "'";
        }
        
        $output[] = '<?php';
        $output[] = 'return array(';
        $output[] = implode(',' . $this->_nl, $arrayLines);
        $output[] = ');';
        $output[] = '?>';
        
        $this->_log($this->_tab . 'Ok');
        
        $this->_log('Writing classmap file: ' . $file, false);
        
        file_put_contents(
            $file,
            implode($this->_nl, $output)
        );
        
        $this->_log($this->_tab . 'Ok');
    }
    
    protected function _log($trace, $nl=true)
    {
        echo $trace . ($nl ? $this->_nl : '');
    }
    
    protected function _computeClassName($modelName)
    {
        return $this->_classPrefix . Utils::camelize($modelName, false);
    }
    
    protected function _prepare()
    {
        foreach ($this->_schema as $modelName => $modelDefinition) {
            if (array_key_exists('extends', $modelDefinition)) {
                $this->_childMap[$modelDefinition['extends']][] = $modelName;
            }
        }
    }

    protected function _generate()
    {
        foreach ($this->_schema  as $modelName => $modelDefinition) {
            $this->_log('Creating model: ' . $modelName, false);
            
            $className = $this->_computeClassName($modelName);
            
            /*
             * Class header
             */

            // Inheritance
            if (array_key_exists('extends', $modelDefinition)) {
                $parentClassName = $this->_classPrefix . Utils::camelize($modelDefinition['extends'], false);
                $classUses = array("use $this->_classNamespace\\$parentClassName;");
            }
            else {
                $parentClassName = 'Mapper';
                $classUses = array("use Couchly\\Model\\$parentClassName;");
            }

            // Uses for embedded
            foreach ($modelDefinition['fields'] as $fieldName => $fieldDefinition) {
                if (array_key_exists($fieldName, $this->_schema)) {
                    $childClassName = $this->_classPrefix . Utils::camelize($fieldName, false);
                    $classUses[] = "use $this->_classNamespace\\$childClassName;";
                }
            }

            //
            $this->_output[] = "<?php" . $this->_nl;
            $this->_output[] = "namespace $this->_classNamespace;" . $this->_nl;
            $this->_output[] = implode($this->_nl, $classUses) . $this->_nl;
            $this->_output[] = "class $className extends $parentClassName";
            $this->_output[] = "{";


            /*
             * Constants
             */

            if (array_key_exists('extends', $modelDefinition)) {
                $facadeModelName = $modelDefinition['extends'];
            }
            else {
                $facadeModelName = $modelName;
            }
            $this->_output[] = $this->_computeConstant('FACADE_MODEL_NAME', "'" . $facadeModelName . "'", array('Model name for initializing facade'));
            $this->_output[] = $this->_computeConstant('MODEL_NAME', "'" . $modelName . "'", array('Model name'));
            
            
            /*
             * Class properties
             */

            // Fields
            foreach ($modelDefinition['fields'] as $fieldName => $fieldDefinition) {
                $this->_addClassProperty($fieldName, $fieldDefinition['type'], self::PHP_KW_PROTECTED, false, true);
            }
            foreach ($this->_classProperties as $propertyName => $propertyDefinition) {
                $this->_output[] = $this->_computeProperty($propertyName, $propertyDefinition['type'], $propertyDefinition['visibility'], $propertyDefinition['is_static']);
            }
            
            
            /*
             * Class methods
             */
            
            // Getters/Setters
            foreach ($this->_classProperties as $propertyName => $propertyDefinition) {
                // Getters
                if (!$propertyDefinition['is_static']) {
                    $this->_output[] = $this->_computeGetter($propertyName, $propertyDefinition['type'], $propertyDefinition['visibility']);
                }
                
                // Setters
                if (!$propertyDefinition['is_static']) {
                    $this->_output[] = $this->_computeSetter($propertyName, $propertyDefinition['type'], $propertyDefinition['visibility']);
                }
            }
            
            // GetModelName()
            $content = $this->_tab . $this->_tab . "return self::MODEL_NAME;";
            $this->_output[] = $this->_computeMethod('getModelName', self::PHP_KW_PUBLIC, false, $content);
            
            // save()
            $content = array();
            if (!isset($modelDefinition['extends'])) {
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
            }
            foreach ($this->_classProperties as $propertyName => $propertyDefinition) {
                if ($propertyDefinition['is_data']) {
                    $value = '$this->_' . Utils::camelize($propertyName);
                    if ($propertyDefinition['type'] === self::PHP_KW_STDCLASS) {
                        $value = 'get_object_vars(' . $value . ')';
                    }
                    $subKey = '';
                    if (isset($modelDefinition['extends'])) {
                        $subKey = "['" . $modelName . "']";
                    }
                    $content[] = $this->_tab . $this->_tab . "\$doc['data']" . $subKey . "['" . $propertyName . "'] = " . $value . ';';
                }
            }
            $content[] = $this->_tab . $this->_tab;
            if (isset($modelDefinition['extends'])) {
                $content[] = $this->_tab . $this->_tab . 'parent::save($doc);';
            }
            else {
                $content[] = $this->_tab . $this->_tab . 'self::_getCouchlyFacade()->save($this->_id, $doc);';
            }
            $this->_output[] = $this->_computeMethod('save', self::PHP_KW_PUBLIC, false, implode($this->_nl, $content), 'array $doc = null');

            // _populate()
            $content = array();
            $content[] = $this->_tab . $this->_tab . "\$this->_id = \$doc->_id;";
            $content[] = $this->_tab . $this->_tab . "\$this->_rev = \$doc->_rev;";
            foreach ($this->_classProperties as $propertyName => $propertyDefinition) {
                if ($propertyDefinition['is_data']) {
                    $subKey = '';
                    if (isset($modelDefinition['extends'])) {
                        $subKey = $modelName . '->';
                    }
                    $var = $value = '$doc->data->' . $subKey . $propertyName;
                    if ($propertyDefinition['type'] === self::PHP_KW_STDCLASS) {
                        $objectClassName = $this->_classPrefix . Utils::camelize($propertyName, false);
                        $var = '$doc->data->' . $propertyName . '->_id';
                        $value = 'new ' . $objectClassName . '(' . $var . ')';
                    }
                    $value = 'isset(' . $var .') ? ' . $value . ' : null';
                    $content[] = $this->_tab . $this->_tab . "\$this->_" . Utils::camelize($propertyName) . " = $value;";
                }
            }
            if (isset($modelDefinition['extends'])) {
                $content[] = $this->_nl . $this->_tab . $this->_tab . 'parent::_populate($doc);';
            }
            $this->_output[] = $this->_computeMethod('populate', self::PHP_KW_PROTECTED, false, implode($this->_nl, $content), $this->_modifyType(self::PHP_KW_STDCLASS) . ' $doc');
            
            // _getChildMap()
            if ($this->_isParentModel($modelName)) {
                $content = array();
                $content[] = $this->_tab . $this->_tab . 'return array(';
                $lines = array();
                foreach ($this->_childMap[$modelName] as $childModelName) {
                    $childClassName = $this->_classPrefix . Utils::camelize($childModelName, false);
                    $lines[] = $this->_tab . $this->_tab . $this->_tab . "'" . $childModelName . "' => '" . $childClassName ."'";
                }
                $content[] = implode(',' . $this->_nl, $lines);
                $content[] = $this->_tab . $this->_tab . ');';
                $this->_output[] = $this->_computeMethod('getChildMap', self::PHP_KW_PROTECTED, true, implode($this->_nl, $content));
            }
            
            
            /*
             * Class footer
             */
            
            $this->_output[] = "}";
            
            $this->_log($this->_tab . 'Ok');
            
            
            /*
             * Generate model file
             */
            
            $this->_writeModel($modelName);
        }
        
        
        /*
         * Generate classmap file
         */
        
        $this->_writeClassmap();
    }
    
    protected function _isParentModel($modelName)
    {
        return (!isset($modelDefinition['extends']) && array_key_exists($modelName, $this->_childMap));
    }

    protected function _modifyType($type)
    {
        return $type === self::PHP_KW_STDCLASS ? '\\' . $type : $type;
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
        foreach ($info as $line) {
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
        $property[] = $this->_computeDocBlock(array('@var ' . $this->_modifyType($type)));
        $property[] = $this->_tab . $visibility . ' ' . ($isStatic ? self::PHP_KW_STATIC . ' ' : '') . ($visibility === self::PHP_KW_PUBLIC ? '$' : '$_') . Utils::camelize($name) . ' = null;';
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
        $getter[] = $this->_computeDocBlock(array('@return ' . $this->_modifyType($type)));
        $getter[] = $this->_tab . self::PHP_KW_PUBLIC . ' ' . self::PHP_KW_FUNCTION . ' get' . Utils::camelize($name, false) . '()';
        $getter[] = $this->_tab . '{';
        $getter[] = $this->_tab . $this->_tab . self::PHP_KW_RETURN . ' $this->' . ($visibility === self::PHP_KW_PUBLIC ? '' : '_') . Utils::camelize($name) . ';';
        $getter[] = $this->_tab . '}';
        return implode($this->_nl, $getter) . $this->_nl;
    }

    protected function _computeSetter($name, $type, $visibility)
    {
        $setter = array();
        $setter[] = $this->_computeDocBlock(array('@param ' . $this->_modifyType($type)));
        $setter[] = $this->_tab . self::PHP_KW_PUBLIC . ' ' . self::PHP_KW_FUNCTION . ' set' . Utils::camelize($name, false) . '($'. Utils::camelize($name) .')';
        $setter[] = $this->_tab . '{';
        $setter[] = $this->_tab . $this->_tab . ' $this->' . ($visibility === self::PHP_KW_PUBLIC ? '' : '_') . Utils::camelize($name) . ' = $'. Utils::camelize($name) . ';';
        $setter[] = $this->_tab . '}';
        return implode($this->_nl, $setter) . $this->_nl;
    }
}