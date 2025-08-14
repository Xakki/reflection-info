<?php

namespace Xakki\ReflectionInfo\DTO;

class AttributeInfo
{
    /** @var string */
    public string $name;
    /** @var array<mixed> */
    public array $arguments;

    /**
     * @param string $name
     * @param array<mixed> $arguments
     */
    public function __construct(string $name, array $arguments)
    {
        $this->name = $name;
        $this->arguments = $arguments;
    }
}
