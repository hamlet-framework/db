<?php

namespace Hamlet\Database\Resolvers;

use Hamlet\Cast\Resolvers\DefaultResolver;
use Hamlet\Cast\Resolvers\SubTypeResolution;

class EntityResolver extends DefaultResolver
{
    /**
     * @var array<class-string,TypeResolver>
     */
    private static array $typeResolvers = [];

    /**
     * @template T
     * @param class-string<T> $type
     * @return TypeResolver<T>
     * @psalm-suppress MixedReturnTypeCoercion
     */
    private function locateTypeResolver(string $type): TypeResolver
    {
        if (!isset(self::$typeResolvers[$type])) {
            $reflectionClass = $this->getReflectionClass($type);
            do {
                if (!$reflectionClass->hasMethod('__resolveType')) {
                    continue;
                }
                $method = $reflectionClass->getMethod('__resolveType');
                return self::$typeResolvers[$type] = new MethodBasedTypeResolver($type, $method);
            } while ($reflectionClass = $reflectionClass->getParentClass());
        }

        if (!isset(self::$typeResolvers[$type])) {
            return self::$typeResolvers[$type] = new ConstantTypeResolver($type);
        }

        return self::$typeResolvers[$type];
    }

    /**
     * @template T as object
     * @param class-string<T> $type
     * @param mixed $value
     * @return SubTypeResolution<T>
     */
    public function resolveSubType(string $type, mixed $value): SubTypeResolution
    {
        $resolvedSubTypeName = $this->locateTypeResolver($type)->resolveType($value);
        return new SubTypeResolution($this->getReflectionClass($resolvedSubTypeName), $this);
    }

    public function ignoreUnmappedProperties(): bool
    {
        return false;
    }
}
