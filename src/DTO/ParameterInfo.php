<?php

namespace Xakki\ReflectionInfo\DTO;

/**
 * DTO for representing information about a method parameter.
 */
class ParameterInfo
{
    public string $name;
    public ?string $type;
    public bool $isVariadic;
    public bool $hasDefaultValue;
    public $defaultValue;
    /** @var AttributeInfo[] */
    public array $attributes;
    public bool $isPromoted;
    public bool $allowsNull;
    public bool $isOptional;

    /**
     * @param string $name
     * @param string|null $type
     * @param bool $isVariadic
     * @param bool $hasDefaultValue
     * @param mixed $defaultValue
     * @param AttributeInfo[] $attributes
     * @param bool $isPromoted
     * @param bool $allowsNull
     * @param bool $isOptional
     */
    public function __construct(
        string $name,
        ?string $type,
        bool $isVariadic,
        bool $hasDefaultValue,
        $defaultValue,
        array $attributes,
        bool $isPromoted,
        bool $allowsNull,
        bool $isOptional
    ) {
        $this->name = $name;
        $this->type = $type;
        $this->isVariadic = $isVariadic;
        $this->hasDefaultValue = $hasDefaultValue;
        $this->defaultValue = $defaultValue;
        $this->attributes = $attributes;
        $this->isPromoted = $isPromoted;
        $this->allowsNull = $allowsNull;
        $this->isOptional = $isOptional;
    }
}
