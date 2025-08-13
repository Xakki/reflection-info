<?php

namespace Xakki\ReflectionInfo;

class ReflectionProxy
{
    /** @var object */
    private $originalObject;
    /** @var ?callable */
    private $callBeforeMethod;
    /** @var ?callable */
    private $callAfterMethod;
    /** @var \ReflectionObject */
    private $reflector;

    /**
     * @param object $originalObject
     * @param callable $callBeforeMethod
     * @param callable|null $callAfterMethod
     */
    public function __construct($originalObject, callable $callBeforeMethod, callable $callAfterMethod = null)
    {
        $this->originalObject = $originalObject;
        $this->callBeforeMethod = $callBeforeMethod;
        $this->callAfterMethod = $callAfterMethod;
        // Создаем рефлектор один раз для эффективности
        $this->reflector = new \ReflectionObject($originalObject);
    }

    /**
     * @param string $methodName
     * @param array $arguments
     * @return mixed
     */
    public function __call($methodName, $arguments)
    {
        $className = $this->reflector->getName();

        if ($this->callBeforeMethod) {
            call_user_func_array($this->callBeforeMethod, [$className, $methodName, $arguments]);
        }

        // 2. Используем рефлексию, чтобы проверить, существует ли метод
        if (!$this->reflector->hasMethod($methodName)) {
            trigger_error("Вызов несуществующего метода: {$className}::{$methodName}", E_USER_ERROR);
            return null;
        }

        // 3. Получаем объект ReflectionMethod
        $method = $this->reflector->getMethod($methodName);

        // ВАЖНО: Проверяем, является ли метод публичным.
        // Вызов приватных методов через прокси — обычно плохая идея.
        if (!$method->isPublic()) {
            trigger_error("Попытка вызова непубличного метода: {$className}::{$methodName}", E_USER_ERROR);
            return null;
        }

        if ($method->isStatic()) {
            $val = $method->invokeArgs(null, $arguments);
        } else {
            $val = $method->invokeArgs($this->originalObject, $arguments);
        }

        if ($this->callAfterMethod) {
            call_user_func_array($this->callAfterMethod, [$className, $methodName, $arguments, $val]);
        }
        return $val;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        $className = $this->reflector->getName();

        if ($this->callBeforeMethod) {
            call_user_func_array($this->callBeforeMethod, [$className, '__get', [$name]]);
        }

        if ($this->reflector->hasProperty($name)) {
            $property = $this->reflector->getProperty($name);
            if (!$property->isPublic()) {
                trigger_error("Попытка доступа к непубличному свойству: {$className}::\${$name}", E_USER_ERROR);
                return null;
            }

            if ($property->isStatic()) {
                $val = $property->getValue();
            } else {
                $val = $property->getValue($this->originalObject);
            }
        } else {
            // Доступ к динамическому свойству
            $val = $this->originalObject->{$name};
        }


        if ($this->callAfterMethod) {
            call_user_func_array($this->callAfterMethod, [$className, '__get', [$name], $val]);
        }
        return $val;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set($name, $value)
    {
        $className = $this->reflector->getName();
        if ($this->callBeforeMethod) {
            call_user_func_array($this->callBeforeMethod, [$className, '__set', [$name, $value]]);
        }

        if ($this->reflector->hasProperty($name)) {
            $property = $this->reflector->getProperty($name);
            if (!$property->isPublic()) {
                trigger_error("Попытка записи в непубличное свойство: {$className}::\${$name}", E_USER_ERROR);
                return;
            }

            if ($property->isStatic()) {
                $property->setValue($value);
            } else {
                $property->setValue($this->originalObject, $value);
            }
        } else {
            // Динамическое свойство
            $this->originalObject->{$name} = $value;
        }

        if ($this->callAfterMethod) {
            call_user_func_array($this->callAfterMethod, [$className, '__set', [$name, $value], null]);
        }
    }

    /**
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->originalObject->{$name});
    }

    /**
     * @param string $name
     * @return void
     */
    public function __unset($name)
    {
        unset($this->originalObject->{$name});
    }

    /**
     * @param string $name
     * @param array $arguments
     */
    public static function __callStatic($name, $arguments)
    {
        // Так как это статический метод, у нас нет доступа к $this->originalObject.
        // Следовательно, мы не можем знать, к какому классу перенаправлять вызов.
        trigger_error("Вызов статических методов через прокси напрямую не поддерживается в данной реализации. Вызывайте статические методы через экземпляр прокси.", E_USER_ERROR);
    }
}
