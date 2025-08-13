<?php

namespace Xakki\ReflectionInfo\DTO;

/**
 * DTO для представления информации о методе класса.
 */
class MethodInfo
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
     * @var ParameterInfo[]
     */
    public $parameters;

    /**
     * @var boolean
     */
    public $isStatic;

    /**
     * @var boolean
     */
    public $isFinal;

    /**
     * @var boolean
     */
    public $isAbstract;

    /**
     * @var string|null
     */
    public $declaringClass;

    /**
     * @param string          $name
     * @param string          $visibility
     * @param string|null     $docComment
     * @param ParameterInfo[] $parameters
     * @param boolean         $isStatic
     * @param boolean         $isFinal
     * @param boolean         $isAbstract
     * @param string|null     $declaringClass
     */
    public function __construct($name, $visibility, $docComment, $parameters, $isStatic, $isFinal, $isAbstract, $declaringClass = null)
    {
        $this->name = $name;
        $this->visibility = $visibility;
        $this->docComment = $docComment;
        $this->parameters = $parameters;
        $this->isStatic = $isStatic;
        $this->isFinal = $isFinal;
        $this->isAbstract = $isAbstract;
        $this->declaringClass = $declaringClass;
    }
}
