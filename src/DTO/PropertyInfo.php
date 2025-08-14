<?php

namespace Xakki\ReflectionInfo\DTO;

/**
 * DTO for representing information about a class property.
 */
class PropertyInfo
{
    public string $name;
    public string $visibility;
    public ?string $docComment;
    public ReflectionData $value;
    public bool $isStatic;
    public ?string $declaringClass;
    public bool $isReadonly;
    public ?string $type;
    /** @var AttributeInfo[] */
    public array $attributes;
    public bool $hasDefaultValue;
    public bool $isInitialized;

    /**
     * @param string $name
     * @param string $visibility
     * @param string|null $docComment
     * @param ReflectionData $value
     * @param bool $isStatic
     * @param string|null $declaringClass
     * @param bool $isReadonly
     * @param string|null $type
     * @param AttributeInfo[] $attributes
     * @param bool $hasDefaultValue
     * @param bool $isInitialized
     */
    public function __construct(
        string $name,
        string $visibility,
        ?string $docComment,
        ReflectionData $value,
        bool $isStatic,
        ?string $declaringClass,
        bool $isReadonly,
        ?string $type,
        array $attributes,
        bool $hasDefaultValue,
        bool $isInitialized
    ) {
        $this->name = $name;
        $this->visibility = $visibility;
        $this->docComment = $docComment;
        $this->value = $value;
        $this->isStatic = $isStatic;
        $this->declaringClass = $declaringClass;
        $this->isReadonly = $isReadonly;
        $this->type = $type;
        $this->attributes = $attributes;
        $this->hasDefaultValue = $hasDefaultValue;
        $this->isInitialized = $isInitialized;
    }
}
