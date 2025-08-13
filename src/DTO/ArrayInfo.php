<?php

namespace Xakki\ReflectionInfo\DTO;

/**
 * DTO для представления проанализированного массива.
 */
class ArrayInfo extends ReflectionData
{
    /**
     * @var array<string|int, ReflectionData>
     */
    public $items = [];

    /**
     * @param array<string|int, ReflectionData> $items
     */
    public function __construct($items = [])
    {
        $this->items = $items;
    }
}
