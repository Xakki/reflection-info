<?php

namespace Xakki\ReflectionInfo\DTO;

/**
 * DTO для представления информации о свойстве класса.
 */
class PropertyInfo
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string Константа из класса Visibility
     */
    public $visibility;

    /**
     * @var string|null
     */
    public $docComment;

    /**
     * @var ReflectionData
     */
    public $value;

    /**
     * @var boolean
     */
    public $isStatic;

    /**
     * @var string|null
     */
    public $declaringClass;

    /**
     * @param string         $name
     * @param string         $visibility
     * @param string|null    $docComment
     * @param ReflectionData $value
     * @param boolean        $isStatic
     * @param string|null    $declaringClass
     */
    public function __construct($name, $visibility, $docComment, $value, $isStatic, $declaringClass = null)
    {
        $this->name = $name;
        $this->visibility = $visibility;
        $this->docComment = $docComment;
        $this->value = $value;
        $this->isStatic = $isStatic;
        $this->declaringClass = $declaringClass;
    }
}
