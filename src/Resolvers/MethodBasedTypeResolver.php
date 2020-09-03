<?php

namespace Hamlet\Database\Resolvers;

use Hamlet\Database\DatabaseException;
use ReflectionMethod;

/**
 * @template T as object
 * @implements TypeResolver<T>
 */
class MethodBasedTypeResolver implements TypeResolver
{
    /**
     * @var string
     * @psalm-var class-string<T>
     */
    private $parentType;

    /**
     * @var ReflectionMethod
     */
    private $reflectionMethod;

    /**
     * @param string $parentType
     * @psalm-param class-string<T> $parentType
     * @param ReflectionMethod $reflectionMethod
     */
    public function __construct(string $parentType, ReflectionMethod $reflectionMethod)
    {
        $this->parentType = $parentType;
        $this->reflectionMethod = $reflectionMethod;
    }

    /**
     * @param mixed $value
     * @return string
     * @psalm-return class-string<T>
     */
    public function resolveType($value): string
    {
        /**
         * @psalm-suppress MixedAssignment
         */
        $subType = $this->reflectionMethod->invoke(null, $value);
        if (!is_string($subType)) {
            throw new DatabaseException('Type resolver must return a string: ' . $this->reflectionMethod->getName());
        }
        if (!class_exists($subType)) {
            throw new DatabaseException('Type resolver must return a valid class-string: ' . $subType . ' returned');
        }
        if (!is_subclass_of($subType, $this->parentType)) {
            throw new DatabaseException('Type resolved outside of inheritance tree: ' . $subType . ' is not subclass of ' . $this->parentType);
        }
        return $subType;
    }
}
