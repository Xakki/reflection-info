<?php

namespace Xakki\ReflectionInfo\DTO;

/**
 * DTO for representing information about a class method.
 */
class MethodInfo
{
    public string $name;
    public string $visibility;
    public ?string $docComment;
    /** @var ParameterInfo[] */
    public array $parameters;
    public bool $isStatic;
    public bool $isFinal;
    public bool $isAbstract;
    public ?string $declaringClass;
    /** @var AttributeInfo[] */
    public array $attributes;
    public ?string $returnType;
    public bool $isGenerator;
    public bool $hasTentativeReturnType;
    public ?string $tentativeReturnType;
    public ?string $traitName = null;
    public bool $isOverride = false;


    /**
     * @param string $name
     * @param string $visibility
     * @param string|null $docComment
     * @param ParameterInfo[] $parameters
     * @param bool $isStatic
     * @param bool $isFinal
     * @param bool $isAbstract
     * @param string|null $declaringClass
     * @param AttributeInfo[] $attributes
     * @param string|null $returnType
     * @param bool $isGenerator
     * @param bool $hasTentativeReturnType
     * @param string|null $tentativeReturnType
     * @param string|null $traitName
     * @param bool $isOverride
     */
    public function __construct(
        string $name,
        string $visibility,
        ?string $docComment,
        array $parameters,
        bool $isStatic,
        bool $isFinal,
        bool $isAbstract,
        ?string $declaringClass,
        array $attributes,
        ?string $returnType,
        bool $isGenerator,
        bool $hasTentativeReturnType,
        ?string $tentativeReturnType,
        ?string $traitName = null,
        bool $isOverride = false
    ) {
        $this->name = $name;
        $this->visibility = $visibility;
        $this->docComment = $docComment;
        $this->parameters = $parameters;
        $this->isStatic = $isStatic;
        $this->isFinal = $isFinal;
        $this->isAbstract = $isAbstract;
        $this->declaringClass = $declaringClass;
        $this->attributes = $attributes;
        $this->returnType = $returnType;
        $this->isGenerator = $isGenerator;
        $this->hasTentativeReturnType = $hasTentativeReturnType;
        $this->tentativeReturnType = $tentativeReturnType;
        $this->traitName = $traitName;
        $this->isOverride = $isOverride;
    }
}
