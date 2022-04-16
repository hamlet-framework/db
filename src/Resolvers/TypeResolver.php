<?php

namespace Hamlet\Database\Resolvers;

/**
 * @template T as object
 */
interface TypeResolver
{
    /**
     * @return class-string<T>
     */
    public function resolveType(mixed $value): string;
}
