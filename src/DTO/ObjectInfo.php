<?php

namespace Xakki\ReflectionInfo\DTO;

/**
 * DTO for representing complete information about an object.
 */
class ObjectInfo extends ReflectionData
{
    /** @var string */
    public $class;
    /** @var string */
    public $hash;
    /** @var string|null */
    public $fileName;
    /** @var int|null */
    public $startLine;
    /** @var int|null */
    public $endLine;
    /** @var string|null */
    public $docComment;
    /** @var bool */
    public $isFinal;
    /** @var bool */
    public $isAbstract;
    /** @var bool */
    public $isCloneable;
    /** @var bool */
    public $isReadonly;
    /** @var bool */
    public $isEnum;
    /** @var string|null */
    public $parent;
    /** @var string[] */
    public $interfaces;
    /** @var string[] */
    public $traits;
    /** @var array<string, scalar> */
    public $constants;
    /** @var array<string, PropertyInfo> */
    public $properties;
    /** @var array<string, MethodInfo> */
    public $methods;
    /** @var AttributeInfo[] */
    public $attributes;
    /** @var array<string, scalar> */
    public $cases;

    /**
     * @param string $class
     * @param string $hash
     * @param string|null $fileName
     * @param int|null $startLine
     * @param int|null $endLine
     * @param string|null $docComment
     * @param bool $isFinal
     * @param bool $isAbstract
     * @param bool $isCloneable
     * @param bool $isReadonly
     * @param bool $isEnum
     * @param string|null $parent
     * @param string[] $interfaces
     * @param string[] $traits
     * @param array<string, scalar> $constants
     * @param array<string, PropertyInfo> $properties
     * @param array<string, MethodInfo> $methods
     * @param AttributeInfo[] $attributes
     * @param array<string, scalar> $cases
     */
    public function __construct(
        $class,
        $hash,
        $fileName,
        $startLine,
        $endLine,
        $docComment,
        $isFinal,
        $isAbstract,
        $isCloneable,
        $isReadonly,
        $isEnum,
        $parent,
        $interfaces,
        $traits,
        $constants,
        $properties,
        $methods,
        $attributes,
        $cases
    ) {
        $this->class = $class;
        $this->hash = $hash;
        $this->fileName = $fileName;
        $this->startLine = $startLine;
        $this->endLine = $endLine;
        $this->docComment = $docComment;
        $this->isFinal = $isFinal;
        $this->isAbstract = $isAbstract;
        $this->isCloneable = $isCloneable;
        $this->isReadonly = $isReadonly;
        $this->isEnum = $isEnum;
        $this->parent = $parent;
        $this->interfaces = $interfaces;
        $this->traits = $traits;
        $this->constants = $constants;
        $this->properties = $properties;
        $this->methods = $methods;
        $this->attributes = $attributes;
        $this->cases = $cases;
    }
}
