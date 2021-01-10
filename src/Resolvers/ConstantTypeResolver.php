<?php

namespace Hamlet\Database\Resolvers;

/**
 * @template T as object
 * @implements TypeResolver<T>
 */
class ConstantTypeResolver implements TypeResolver
{
    /**
     * @var class-string<T>
     */
    private $type;

    /**
     * @param class-string<T> $type
     */
    public function __construct(string $type)
    {
        $this->type = $type;
    }

    /**
     * @param mixed $value
     * @return class-string<T>
     */
    public function resolveType($value): string
    {
        return $this->type;
    }
}
