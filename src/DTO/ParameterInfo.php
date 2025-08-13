<?php

namespace Xakki\ReflectionInfo\DTO;

/**
 * DTO для представления информации о параметре метода.
 */
class ParameterInfo
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string|null
     */
    public $type;

    /**
     * @var boolean
     */
    public $isVariadic;

    /**
     * @var boolean
     */
    public $hasDefaultValue;

    /**
     * @var mixed
     */
    public $defaultValue;

    /**
     * @param string      $name
     * @param string|null $type
     * @param boolean     $isVariadic
     * @param boolean     $hasDefaultValue
     * @param mixed       $defaultValue
     */
    public function __construct($name, $type, $isVariadic, $hasDefaultValue, $defaultValue)
    {
        $this->name = $name;
        $this->type = $type;
        $this->isVariadic = $isVariadic;
        $this->hasDefaultValue = $hasDefaultValue;
        $this->defaultValue = $defaultValue;
    }
}
