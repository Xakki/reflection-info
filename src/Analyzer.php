<?php

namespace Xakki\ReflectionInfo;

use ReflectionAttribute;
use ReflectionObject;
use ReflectionProperty;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionNamedType;
use ReflectionUnionType;
use ReflectionIntersectionType;
use Xakki\ReflectionInfo\DTO\ArrayInfo;
use Xakki\ReflectionInfo\DTO\MethodInfo;
use Xakki\ReflectionInfo\DTO\ObjectInfo;
use Xakki\ReflectionInfo\DTO\ParameterInfo;
use Xakki\ReflectionInfo\DTO\PropertyInfo;
use Xakki\ReflectionInfo\DTO\ReflectionData;
use Xakki\ReflectionInfo\DTO\ScalarValue;
use Xakki\ReflectionInfo\DTO\Visibility;
use Xakki\ReflectionInfo\DTO\AttributeInfo;

class Analyzer
{
    private int $maxDepth;
    /** @var array<string, bool> */
    private array $analyzedObjects = [];

    public function __construct(int $maxDepth = 5)
    {
        $this->maxDepth = $maxDepth;
    }

    /**
     * @param mixed $data
     * @return ReflectionData
     */
    public function analyze($data): ReflectionData
    {
        $this->analyzedObjects = [];
        return $this->processValue($data, 0);
    }

    /**
     * @param mixed $value
     * @param int $currentDepth
     * @return ReflectionData
     */
    private function processValue($value, int $currentDepth): ReflectionData
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

    private function processObject(object $object, int $currentDepth): ObjectInfo
    {
        $reflector = new ReflectionObject($object);
        $parent = $reflector->getParentClass();

        return new ObjectInfo(
            $reflector->getName(),
            spl_object_hash($object),
            $reflector->getFileName() ?: null,
            $reflector->getStartLine() ?: null,
            $reflector->getEndLine() ?: null,
            $this->parseDocComment($reflector->getDocComment()),
            $reflector->isFinal(),
            $reflector->isAbstract(),
            $reflector->isCloneable(),
            method_exists($reflector, 'isReadonly') && $reflector->isReadonly(),
            method_exists($reflector, 'isEnum') && $reflector->isEnum(),
            $parent ? $parent->getName() : null,
            $reflector->getInterfaceNames(),
            $reflector->getTraitNames(),
            $reflector->getConstants(),
            $this->extractProperties($reflector, $object, $currentDepth),
            $this->extractMethods($reflector),
            $this->extractAttributes($reflector),
            method_exists($reflector, 'isEnum') && $reflector->isEnum() ? $this->extractEnumCases($reflector) : []
        );
    }

    /**
     * @return array<string, PropertyInfo>
     */
    private function extractProperties(ReflectionObject $reflector, object $object, int $currentDepth): array
    {
        $properties = [];
        $flags = ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED | ReflectionProperty::IS_PRIVATE;

        foreach ($reflector->getProperties($flags) as $p) {
            $p->setAccessible(true);
            $properties[$p->getName()] = new PropertyInfo(
                $p->getName(),
                $this->getVisibility($p),
                $this->parseDocComment($p->getDocComment()),
                $this->processValue($p->isInitialized($object) ? $p->getValue($object) : '[uninitialized]', $currentDepth + 1),
                $p->isStatic(),
                $p->getDeclaringClass()->getName(),
                method_exists($p, 'isReadonly') && $p->isReadonly(),
                $this->formatType($p->getType()),
                $this->extractAttributes($p),
                $p->hasDefaultValue(),
                $p->isInitialized($object)
            );
        }
        return $properties;
    }

    /**
     * @return array<string, MethodInfo>
     */
    private function extractMethods(ReflectionObject $reflector): array
    {
        $methods = [];
        $flags = ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED | ReflectionMethod::IS_PRIVATE;

        $parent = $reflector->getParentClass();

        foreach ($reflector->getMethods($flags) as $m) {
            $declaringClass = $m->getDeclaringClass();
            $traitName = null;

            if ($declaringClass->isTrait()) {
                $traitName = $declaringClass->getName();
            } elseif ($declaringClass->getName() === $reflector->getName()) {
                // Method is defined in the current class. Check if it overrides a trait method.
                foreach ($reflector->getTraits() as $trait) {
                    if ($trait->hasMethod($m->getName())) {
                        $traitName = $trait->getName();
                        break; // Found the trait, no need to check others
                    }
                }
            }

            $isOverride = false;
            if ($parent && $parent->hasMethod($m->getName()) && $declaringClass->getName() === $reflector->getName()) {
                $parentMethod = $parent->getMethod($m->getName());
                if ($parentMethod->isAbstract()) {
                    $isOverride = true;
                }
            }

            $tentativeReturnType = null;
            if (method_exists($m, 'hasTentativeReturnType') && $m->hasTentativeReturnType()) {
                $tentativeReturnType = $this->formatType($m->getTentativeReturnType());
            }

            $methods[$m->getName()] = new MethodInfo(
                $m->getName(),
                $this->getVisibility($m),
                $this->parseDocComment($m->getDocComment()),
                $this->extractParameters($m),
                $m->isStatic(),
                $m->isFinal(),
                $m->isAbstract(),
                $declaringClass->getName(),
                $this->extractAttributes($m),
                $this->formatType($m->getReturnType()),
                $m->isGenerator(),
                method_exists($m, 'hasTentativeReturnType') && $m->hasTentativeReturnType(),
                $tentativeReturnType,
                $traitName,
                $isOverride
            );
        }
        return $methods;
    }

    /**
     * @return ParameterInfo[]
     */
    private function extractParameters(ReflectionMethod $method): array
    {
        $params = [];
        foreach ($method->getParameters() as $param) {
            $params[] = new ParameterInfo(
                '$' . $param->getName(),
                $this->formatType($param->getType()),
                $param->isVariadic(),
                $param->isDefaultValueAvailable(),
                $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null,
                $this->extractAttributes($param),
                method_exists($param, 'isPromoted') && $param->isPromoted(),
                $param->allowsNull(),
                $param->isOptional()
            );
        }
        return $params;
    }

    /**
     * @param \ReflectionClassConstant|\ReflectionObject|\ReflectionProperty|\ReflectionMethod|\ReflectionParameter $reflection
     * @return AttributeInfo[]
     */
    private function extractAttributes($reflection): array
    {
        if (!method_exists($reflection, 'getAttributes')) {
            return [];
        }
        $attributes = [];
        foreach ($reflection->getAttributes() as $attribute) {
            $attributes[] = new AttributeInfo(
                $attribute->getName(),
                $attribute->getArguments()
            );
        }
        return $attributes;
    }

    /**
     * @param \ReflectionEnum $reflector
     * @return array<string, scalar>
     */
    private function extractEnumCases(object $reflector): array
    {
        $cases = [];
        foreach ($reflector->getCases() as $case) {
            $cases[$case->getName()] = $case->getValue();
        }
        return $cases;
    }

    private function formatType($type): ?string
    {
        if (!$type) {
            return null;
        }
        if ($type instanceof ReflectionNamedType) {
            return ($type->allowsNull() && $type->getName() !== 'mixed' ? '?' : '') . $type->getName();
        }
        if ($type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType) {
            $separator = $type instanceof ReflectionUnionType ? '|' : '&';
            return implode($separator, array_map([$this, 'formatType'], $type->getTypes()));
        }
        return (string)$type;
    }

    /**
     * @param \ReflectionProperty|\ReflectionMethod $reflection
     */
    private function getVisibility($reflection): string
    {
        if ($reflection->isPublic()) {
            return Visibility::V_PUBLIC;
        }
        if ($reflection->isProtected()) {
            return Visibility::V_PROTECTED;
        }
        return Visibility::V_PRIVATE;
    }

    private function parseDocComment($comment): ?string
    {
        if (!$comment) {
            return null;
        }
        $lines = array_map(fn ($line) => ltrim(trim($line), '/* '), explode("\n", $comment));
        return trim(implode("\n", $lines));
    }
}
