<?php

namespace Hamlet\Database\Traits;

use Hamlet\Database\Entity;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use RuntimeException;
use function is_array;
use function is_subclass_of;

trait EntityFactoryTrait
{
    /**
     * @var array
     * @psalm-var array<string,\ReflectionClass>
     */
    private static $types = [];

    /**
     * @var array
     * @psalm-var array<string,array<string,\ReflectionProperty>>
     */
    private static $properties = [];

    /**
     * @var array
     * @psalm-var array<string,bool>
     */
    private static $entitySubclasses = [];

    /**
     * @var array
     * @psalm-var array<string,\ReflectionMethod>
     */
    private static $typeResolvers = [];

    /**
     * @param mixed $item
     * @return bool
     * @psalm-suppress MixedAssignment
     * @psalm-assert !null $item
     */
    private function isNull($item): bool
    {
        if (is_array($item)) {
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
     * @param string $typeName
     * @psalm-param class-string<S> $typeName
     * @param array<string,mixed> $data
     * @psalm-param array<string,T> $data
     * @return mixed
     * @psalm-return S|null
     */
    private function instantiate(string $typeName, array $data)
    {
        if ($this->isNull($data)) {
            return null;
        }

        if (!isset(self::$entitySubclasses[$typeName])) {
            self::$entitySubclasses[$typeName] = is_subclass_of($typeName, Entity::class);
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
     * @param string $typeName
     * @psalm-param class-string<S> $typeName
     * @param array<string,mixed> $data
     * @psalm-param array<string,T> $data
     * @return mixed
     * @psalm-return S
     */
    private function instantiateEntity(string $typeName, array $data)
    {
        /**
         * @var ReflectionClass $type
         * @var array<string,\ReflectionProperty> $properties
         * @var ReflectionMethod|null $typeResolver
         */
        list($type, $properties, $typeResolver) = $this->getType($typeName);

        if ($typeResolver) {
            /**
             * @psalm-suppress MixedAssignment
             */
            $resolvedTypeName = $typeResolver->invoke(null, $data);
            if (!is_string($resolvedTypeName)) {
                throw new RuntimeException('Resolved type name must be a string, provided ' . var_export($resolvedTypeName, true));
            }
            if (!class_exists($resolvedTypeName)) {
                throw new RuntimeException('Cannot find class ' . $resolvedTypeName);
            }
            /**
             * @var ReflectionClass $resolvedType
             * @var array<string,\ReflectionProperty> $resolvedProperties
             */
            list($resolvedType, $resolvedProperties) = $this->getType($resolvedTypeName);
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

        /**
         * @noinspection PhpUnusedLocalVariableInspection
         * @psalm-suppress MixedAssignment
         */
        foreach ($properties as $name => &$_) {
            if (!isset($propertiesSet[$name])) {
                throw new RuntimeException('Property ' . $typeName . '::' . $name . ' not set in ' . json_encode($data));
            }
        }

        return $object;
    }

    /**
     * @template S
     * @param string $typeName
     * @psalm-param class-string<S> $typeName
     * @return array
     * @psalm-return array{0:\ReflectionClass,1:array<string,\ReflectionProperty>,2:\ReflectionMethod|null}
     */
    private function getType(string $typeName): array
    {
        if (!isset(self::$types[$typeName])) {
            self::$properties[$typeName] = [];
            try {
                $type = new ReflectionClass($typeName);
                self::$types[$typeName] = $type;
            } catch (ReflectionException $e) {
                throw new RuntimeException('Cannot load reflection information for ' . $typeName, 1, $e);
            }

            foreach ($type->getProperties() as &$property) {
                $property->setAccessible(true);
                self::$properties[$typeName][$property->getName()] = $property;
            }

            do {
                if (!$type->hasMethod('__resolveType')) {
                    continue;
                }
                try {
                    $method = $type->getMethod('__resolveType');
                } catch (ReflectionException $e) {
                    throw new RuntimeException('Cannot access __resolveType method', 0, $e);
                }
                if (!$method->isStatic() || !$method->isPublic()) {
                    throw new RuntimeException('Method __resolveType must be public static method');
                }
                assert($method instanceof ReflectionMethod);
                self::$typeResolvers[$typeName] = $method;
                break;
            } while ($type = $type->getParentClass());
        }

        /** @var ReflectionClass $type */
        $type = self::$types[$typeName];

        /** @var array<string,\ReflectionProperty> $properties */
        $properties = self::$properties[$typeName];

        /** @var ReflectionMethod|null $typeResolver */
        $typeResolver = self::$typeResolvers[$typeName] ?? null;

        return [$type, $properties, $typeResolver];
    }
}
