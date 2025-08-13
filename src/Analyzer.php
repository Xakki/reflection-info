<?php

namespace Xakki\ReflectionInfo;

use ReflectionObject;
use ReflectionProperty;
use ReflectionMethod;
use ReflectionParameter;
use Xakki\ReflectionInfo\DTO\ArrayInfo;
use Xakki\ReflectionInfo\DTO\MethodInfo;
use Xakki\ReflectionInfo\DTO\ObjectInfo;
use Xakki\ReflectionInfo\DTO\ParameterInfo;
use Xakki\ReflectionInfo\DTO\PropertyInfo;
use Xakki\ReflectionInfo\DTO\ReflectionData;
use Xakki\ReflectionInfo\DTO\ScalarValue;
use Xakki\ReflectionInfo\DTO\Visibility;

class Analyzer
{
    /**
     * @var int Maximum recursion depth for object analysis
     */
    private $maxDepth = 5;

    /**
     * @var array<string, boolean> Stores hashes of already analyzed objects to avoid infinite recursion
     */
    private $analyzedObjects = [];

    /**
     * @param int $maxDepth
     */
    public function __construct($maxDepth = 5)
    {
        $this->maxDepth = (int)$maxDepth;
    }

    /**
     * @param mixed $data
     * @return ReflectionData
     */
    public function analyze($data)
    {
        $this->analyzedObjects = [];
        return $this->processValue($data, 0);
    }

    /**
     * @param mixed $value
     * @param int   $currentDepth
     * @return ReflectionData
     */
    private function processValue($value, $currentDepth)
    {
        if (is_object($value)) {
            $hash = spl_object_hash($value);
            if (isset($this->analyzedObjects[$hash]) || $currentDepth >= $this->maxDepth) {
                return new ScalarValue('[Recursion or max depth reached: ' . get_class($value) . ']');
            }
            $this->analyzedObjects[$hash] = true;
            return $this->processObject($value, $currentDepth);
        }

        if (is_array($value)) {
            $items = [];
            foreach ($value as $key => $element) {
                $items[$key] = $this->processValue($element, $currentDepth + 1);
            }
            return new ArrayInfo($items);
        }

        return new ScalarValue($value);
    }

    /**
     * @param object $object
     * @param int    $currentDepth
     * @return ObjectInfo
     */
    private function processObject($object, $currentDepth)
    {
        $reflector = new ReflectionObject($object);
        $parent = $reflector->getParentClass();

        return new ObjectInfo(
            $reflector->getName(),
            spl_object_hash($object),
            $reflector->getFileName() ? $reflector->getFileName() : null,
            $reflector->getStartLine() ? $reflector->getStartLine() : null,
            $reflector->getEndLine() ? $reflector->getEndLine() : null,
            $this->parseDocComment($reflector->getDocComment()),
            $reflector->isFinal(),
            $reflector->isAbstract(),
            $reflector->isCloneable(),
            $parent ? $parent->getName() : null,
            $reflector->getInterfaceNames(),
            $reflector->getTraitNames(),
            $reflector->getConstants(),
            $this->extractProperties($reflector, $object, $currentDepth),
            $this->extractMethods($reflector)
        );
    }

    /**
     * @param \ReflectionObject $reflector
     * @param object            $object
     * @param int               $currentDepth
     * @return array<string, PropertyInfo>
     */
    private function extractProperties(ReflectionObject $reflector, $object, $currentDepth)
    {
        $properties = [];
        $flags = ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED | ReflectionProperty::IS_PRIVATE;

        foreach ($reflector->getProperties($flags) as $p) {
            $p->setAccessible(true);
            $properties[$p->getName()] = new PropertyInfo(
                $p->getName(),
                $this->getVisibility($p),
                $this->parseDocComment($p->getDocComment()),
                $this->processValue($p->getValue($object), $currentDepth + 1),
                $p->isStatic(),
                $p->getDeclaringClass()->getName()
            );
        }
        return $properties;
    }

    /**
     * @param \ReflectionObject $reflector
     * @return array<string, MethodInfo>
     */
    private function extractMethods(ReflectionObject $reflector)
    {
        $methods = [];
        $flags = ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED | ReflectionMethod::IS_PRIVATE;

        foreach ($reflector->getMethods($flags) as $m) {
            $methods[$m->getName()] = new MethodInfo(
                $m->getName(),
                $this->getVisibility($m),
                $this->parseDocComment($m->getDocComment()),
                $this->extractParameters($m),
                $m->isStatic(),
                $m->isFinal(),
                $m->isAbstract(),
                $m->getDeclaringClass()->getName()
            );
        }
        return $methods;
    }

    /**
     * @param \ReflectionMethod $method
     * @return ParameterInfo[]
     */
    private function extractParameters(ReflectionMethod $method)
    {
        $params = [];
        foreach ($method->getParameters() as $param) {
            $params[] = new ParameterInfo(
                '$' . $param->getName(),
                $this->getParameterType($param),
                method_exists($param, 'isVariadic') ? $param->isVariadic() : false,
                $param->isDefaultValueAvailable(),
                $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null
            );
        }
        return $params;
    }

    /**
     * @param \ReflectionParameter $param
     * @return string
     */
    private function getParameterType(ReflectionParameter $param)
    {
        if ($param->isArray()) {
            return 'array';
        }
        $class = $param->getClass();
        if ($class) {
            return $class->getName();
        }
        if (method_exists($param, 'isCallable') && $param->isCallable()) {
            return 'callable';
        }
        return 'mixed';
    }

    /**
     * @param \ReflectionProperty|\ReflectionMethod $reflection
     * @return string
     */
    private function getVisibility($reflection)
    {
        if ($reflection->isPublic()) {
            return Visibility::V_PUBLIC;
        }
        if ($reflection->isProtected()) {
            return Visibility::V_PROTECTED;
        }
        return Visibility::V_PRIVATE;
    }

    /**
     * @param string|false|null $comment
     * @return string|null
     */
    private function parseDocComment($comment)
    {
        if (!$comment) {
            return null;
        }
        $lines = array_map(function ($line) {
            return ltrim(trim($line), '/* ');
        }, explode("\n", $comment));
        return trim(implode("\n", $lines));
    }
}
