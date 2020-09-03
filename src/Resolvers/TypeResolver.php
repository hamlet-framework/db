<?php

namespace Hamlet\Database\Resolvers;

/**
 * @template T as object
 */
interface TypeResolver
{
    /**
     * @param mixed $value
     * @return string
     * @psalm-return class-string<T>
     */
    public function resolveType($value): string;
}
