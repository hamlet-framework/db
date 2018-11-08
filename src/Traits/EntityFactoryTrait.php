<?php

namespace Hamlet\Database\Traits;

use Hamlet\Database\Entity;
use ReflectionClass;
use ReflectionException;
use RuntimeException;

trait EntityFactoryTrait
{
    /** @var array<string,\ReflectionClass> $types */
    private static $types = [];

    /** @var array<string,array<string,\ReflectionProperty>> $properties */
    private static $properties = [];

    /** @var array<string,bool> */
    private static $entitySubclasses = [];

    /**
     * @param mixed $item
     * @return bool
     */
    private function isNull($item): bool
    {
        if (\is_array($item)) {
            foreach ($item as &$value) {
                if (!$this->isNull($value)) {
                    return false;
                }
            }
            return true;
        } else {
            return $item === null;
        }
    }

    /**
     * @template S
     * @template T
     * @template-typeof S $typeName
     * @param array<string,T> $data
     * @return S|null
     */
    private function instantiate(string $typeName, array $data)
    {
        if ($this->isNull($data)) {
            return null;
        }

        if (!isset(self::$entitySubclasses[$typeName])) {
            self::$entitySubclasses[$typeName] = \is_subclass_of($typeName, Entity::class);
        }

        if (self::$entitySubclasses[$typeName]) {
            return $this->instantiateEntity($typeName, $data);
        }

        $object = new $typeName();
        foreach ($data as $key => &$value) {
            $object->$key = $value;
        }
        return $object;
    }

    /**
     * @template S
     * @template T
     * @template-typeof S $typeName
     * @param array<string,T> $data
     * @return S
     */
    private function instantiateEntity(string $typeName, array $data)
    {
        if (!isset(self::$types[$typeName])) {
            self::$properties[$typeName] = [];
            try {
                $type = new ReflectionClass($typeName);
                self::$types[$typeName] = $type;
                foreach ($type->getProperties() as &$property) {
                    $property->setAccessible(true);
                    self::$properties[$typeName][$property->getName()] = $property;
                }
            } catch (ReflectionException $e) {
                throw new RuntimeException('Cannot load reflection information for ' . $typeName, 1, $e);
            }
        }

        /** @var \ReflectionClass $type */
        $type = self::$types[$typeName];
        $object = $type->newInstanceWithoutConstructor();

        $propertiesSet = [];
        foreach ($data as $name => &$value) {
            if (!isset(self::$properties[$typeName][$name])) {
                throw new RuntimeException('Property ' . $name . ' not found in class ' . $typeName);
            }
            $propertiesSet[$name] = 1;
            /** @var \ReflectionProperty $property */
            $property = self::$properties[$typeName][$name];
            $property->setValue($object, $value);
        }

        /** @noinspection PhpUnusedLocalVariableInspection */
        foreach (self::$properties[$typeName] as $name => &$_) {
            if (!isset($propertiesSet[$name])) {
                throw new RuntimeException('Property ' . $typeName . '::' . $name . ' not set in ' . json_encode($data));
            }
        }

        return $object;
    }
}
