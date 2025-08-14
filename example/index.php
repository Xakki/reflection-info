<?php

require __DIR__ . '/../vendor/autoload.php';

use Xakki\ReflectionInfo\Analyzer;
use Xakki\ReflectionInfo\ReflectionInfo;

interface MyInterface
{
    public function methodFromInterface(string $param1, bool $param2 = true): void;
}

trait myTrait {
    protected function doSomething(array $param = []): string
    {
        return 'FALSE';
    }
    protected function traitMethod($param): bool
    {
        return false;
    }
}

abstract class AbstractClass implements MyInterface{
    public static bool $snakeAttributes = false;
    public const CREATED_AT = 'createdAt';
    protected string $name;

    protected function myProtectedMethod(): void
    {
        // ...
    }
}

class MyClass extends AbstractClass {
    use myTrait;
    private string $secret = 's3cr3t';
    private int $publicProp = 123;

    public function methodFromInterface(string $param1, bool $param2 = true): void
    {
        // ...
    }

    protected function doSomething(array $param = []): string
    {
        return 'OK';
    }

    public static function myStaticMethod(): MyClass
    {
        return new MyClass();
    }
}

$myObject = new MyClass();

ReflectionInfo::renderObjectInfo($myObject);