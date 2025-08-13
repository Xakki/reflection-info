<?php

namespace Xakki\ReflectionInfo\DTO;

/**
 * DTO для представления полной информации об объекте.
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
    /** @var boolean */
    public $isFinal;
    /** @var boolean */
    public $isAbstract;
    /** @var boolean */
    public $isCloneable;
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

    /**
     * @param string                    $class
     * @param string                    $hash
     * @param string|null               $fileName
     * @param int|null                  $startLine
     * @param int|null                  $endLine
     * @param string|null               $docComment
     * @param boolean                   $isFinal
     * @param boolean                   $isAbstract
     * @param boolean                   $isCloneable
     * @param string|null               $parent
     * @param string[]                  $interfaces
     * @param string[]                  $traits
     * @param array<string, scalar>     $constants
     * @param array<string, PropertyInfo> $properties
     * @param array<string, MethodInfo>   $methods
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
        $parent,
        $interfaces,
        $traits,
        $constants,
        $properties,
        $methods
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
        $this->parent = $parent;
        $this->interfaces = $interfaces;
        $this->traits = $traits;
        $this->constants = $constants;
        $this->properties = $properties;
        $this->methods = $methods;
    }
}
