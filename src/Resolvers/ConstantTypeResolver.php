<?php

namespace Hamlet\Database\Resolvers;

/**
 * @template T as object
 * @implements TypeResolver<T>
 */
class ConstantTypeResolver implements TypeResolver
{
    /**
     * @param class-string<T> $type
     */
    public function __construct(private readonly string $type)
    {
    }

    /**
     * @return class-string<T>
     */
    public function resolveType(mixed $value): string
    {
        return $this->type;
    }
}
