<?php

namespace Hamlet\Database\Resolvers;

use Hamlet\Database\DatabaseException;
use ReflectionException;
use ReflectionMethod;

/**
 * @template T as object
 * @implements TypeResolver<T>
 */
class MethodBasedTypeResolver implements TypeResolver
{
    /**
     * @var class-string<T>
     */
    private $parentType;

    /**
     * @var ReflectionMethod
     */
    private $reflectionMethod;

    /**
     * @param class-string<T> $parentType
     * @param ReflectionMethod $reflectionMethod
     */
    public function __construct(string $parentType, ReflectionMethod $reflectionMethod)
    {
        $this->parentType = $parentType;
        $this->reflectionMethod = $reflectionMethod;
    }

    /**
     * @param mixed $value
     * @return class-string<T>
     * @throws ReflectionException
     */
    public function resolveType($value): string
    {
        if (!$this->reflectionMethod->isStatic()) {
            throw new DatabaseException(sprintf('Type resolver must be static: %s', $this->reflectionMethod->getDeclaringClass()->getName()));
        }
        if (!$this->reflectionMethod->isPublic()) {
            throw new DatabaseException(sprintf('Type resolver must be public: %s', $this->reflectionMethod->getDeclaringClass()->getName()));
        }
        $subType = $this->reflectionMethod->invoke(null, $value);
        if (!is_string($subType)) {
            throw new DatabaseException(sprintf('Type resolver must return a string: %s', $this->reflectionMethod->getDeclaringClass()->getName()));
        }
        if (!class_exists($subType)) {
            throw new DatabaseException(sprintf('Type resolver must return a valid class-string: %s returned', $subType));
        }
        if (!is_subclass_of($subType, $this->parentType)) {
            throw new DatabaseException(sprintf('Type resolved outside of inheritance tree: %s is not subclass of %s', $subType, $this->parentType));
        }
        return $subType;
    }
}
