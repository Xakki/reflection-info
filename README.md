# Reflection Info

[![Latest Stable Version](https://img.shields.io/packagist/v/xakki/reflection-info.svg)](https://packagist.org/packages/xakki/reflection-info)
[![Total Downloads](https://img.shields.io/packagist/dt/xakki/reflection-info.svg)](https://packagist.org/packages/xakki/reflection-info)
[![License](https://img.shields.io/packagist/l/xakki/reflection-info.svg)](https://packagist.org/packages/xakki/reflection-info)

A PHP library for deep, recursive analysis of variables, objects, and classes using reflection. It provides a structured and detailed view of your data, making it ideal for debugging, logging, and serialization tasks.

## Features

-   **Recursive Analysis**: Traverses nested objects and arrays to build a complete data tree.
-   **Circular Reference Protection**: Detects and handles recursion to prevent infinite loops.
-   **Depth Control**: Allows you to set a maximum depth for the analysis to manage performance and output size.
-   **Detailed Class Information**: Extracts class name, parent class, interfaces, traits, constants, file path, and doc comments.
-   **Comprehensive Property Details**: Gathers property names, visibility, values, static status, and doc comments.
-   **In-depth Method Analysis**: Retrieves method names, visibility, parameters (with types and default values), static/final/abstract status, and doc comments.
-   **Structured DTOs**: Returns the analysis result as a clean and easy-to-navigate tree of Data Transfer Objects (DTOs).

## Installation

Install the library using [Composer](https://getcomposer.org/):

```bash
composer require xakki/reflection-info
```

## Usage

Using the library is straightforward. Instantiate the `Analyzer` and pass the variable you want to inspect to the `analyze()` method.

```php
<?php

require 'vendor/autoload.php';

use Xakki\ReflectionInfo\Analyzer;
use Xakki\ReflectionInfo\ReflectionInfo;

class MyClass {
    private string $secret = 's3cr3t';
    public int $publicProp = 123;
    
    public function doSomething(string $param1, bool $param2 = true): void
    {
        // ...
    }
}

$myObject = new MyClass();

ReflectionInfo::renderObjectInfo($myObject);

```


## License

This library is licensed under the MIT License.