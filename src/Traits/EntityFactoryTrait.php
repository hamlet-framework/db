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

    /** @var array<string,\ReflectionMethod> */
    private static $typeResolvers = [];

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
        /**
         * @var \ReflectionClass $type
         * @var \ReflectionProperty[] $properties
         * @var \ReflectionMethod|null $typeResolver
         */
        list($type, $properties, $typeResolver) = $this->getType($typeName);

        if ($typeResolver) {
            /**
             * @var \ReflectionClass $resolvedType
             * @var \ReflectionProperty[] $resolvedProperties
             */
            list($resolvedType, $resolvedProperties) = $this->getType($typeResolver->invoke(null, $data));
            if ($resolvedType !== $type && !$resolvedType->isSubclassOf($type)) {
                throw new RuntimeException('Resolved type ' . $resolvedType->getName() . ' is not subclass of ' . $type->getName());
            }
            $type = $resolvedType;
            $properties = $resolvedProperties;
        }

        $object = $type->newInstanceWithoutConstructor();
        $propertiesSet = [];
        foreach ($data as $name => &$value) {
            if (!isset($properties[$name])) {
                throw new RuntimeException('Property ' . $name . ' not found in class ' . $typeName);
            }
            $propertiesSet[$name] = 1;
            $property = $properties[$name];
            $property->setValue($object, $value);
        }

        /** @noinspection PhpUnusedLocalVariableInspection */
        foreach ($properties as $name => &$_) {
            if (!isset($propertiesSet[$name])) {
                throw new RuntimeException('Property ' . $typeName . '::' . $name . ' not set in ' . json_encode($data));
            }
        }

        return $object;
    }

    private function getType(string $typeName): array
    {
        if (!isset(self::$types[$typeName])) {
            self::$properties[$typeName] = [];
            try {
                $type = new ReflectionClass($typeName);
            } catch (ReflectionException $e) {
                throw new RuntimeException('Cannot load reflection information for ' . $typeName, 1, $e);
            }

            self::$types[$typeName] = $type;
            foreach ($type->getProperties() as &$property) {
                $property->setAccessible(true);
                self::$properties[$typeName][$property->getName()] = $property;
            }

            /** @var \ReflectionClass $type */
            $type = self::$types[$typeName];
            do {
                if ($type->hasMethod('__resolveType')) {
                    try {
                        $method = $type->getMethod('__resolveType');
                    } catch (ReflectionException $e) {
                        throw new RuntimeException('Cannot access __resolveType method', $e->getCode(), $e);
                    }

                    if (!$method->isStatic() || !$method->isPublic()) {
                        throw new RuntimeException('Method __resolveType must be public static method');
                    }
                    self::$typeResolvers[$typeName] = $method;
                    break;
                }
            } while ($type = $type->getParentClass());
        }

        return [self::$types[$typeName], self::$properties[$typeName], self::$typeResolvers[$typeName] ?? null];
    }
}
