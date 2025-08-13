<?php

namespace Xakki\ReflectionInfo\DTO;

/**
 * DTO для представления скалярных значений, null или сообщений об ошибках.
 */
class ScalarValue extends ReflectionData
{
    /**
     * @var mixed
     */
    public $value;

    /**
     * @param mixed $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }
}
