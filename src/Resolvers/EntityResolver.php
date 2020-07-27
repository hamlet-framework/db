<?php

namespace Hamlet\Database\Resolvers;

use Hamlet\Cast\Resolvers\DefaultResolver;
use Hamlet\Cast\Resolvers\SubTypeResolution;
use ReflectionException;
use ReflectionMethod;
use RuntimeException;

class EntityResolver extends DefaultResolver
{
    /**
     * @var (ReflectionMethod|string)[]
     * @psalm-var array<string,ReflectionMethod|string>
     */
    private static $typeResolvers = [];

    /**
     * @template T
     * @param string $type
     * @psalm-param class-string<T> $type
     * @param mixed $value
     * @return SubTypeResolution
     * @psalm-return SubTypeResolution<T>
     *
     * @psalm-suppress InvalidReturnStatement
     * @psalm-suppress InvalidReturnType
     * @psalm-suppress MixedArgumentTypeCoercion
     * @psalm-suppress MixedReturnTypeCoercion
     */
    public function resolveSubType(string $type, $value): SubTypeResolution
    {
        if (!isset(self::$typeResolvers[$type])) {
            $reflectionClass = $this->getReflectionClass($type);
            do {
                if (!$reflectionClass->hasMethod('__resolveType')) {
                    continue;
                }
                try {
                    $method = $reflectionClass->getMethod('__resolveType');
                } catch (ReflectionException $e) {
                    throw new RuntimeException('Cannot access __resolveType method', 0, $e);
                }
                if (!$method->isStatic() || !$method->isPublic()) {
                    throw new RuntimeException('Method __resolveType must be public static method');
                }
                self::$typeResolvers[$type] = $method;
                break;
            } while ($reflectionClass = $reflectionClass->getParentClass());
        }

        if (!isset(self::$typeResolvers[$type])) {
            self::$typeResolvers[$type] = $type;
        }

        $resolver = self::$typeResolvers[$type];
        if ($resolver instanceof ReflectionMethod) {
            /**
             * @var string $resolvedTypeName
             */
            $resolvedTypeName = $resolver->invoke(null, $value);
            return new SubTypeResolution($this->getReflectionClass($resolvedTypeName), $this);
        } else {
            return new SubTypeResolution($this->getReflectionClass($type), $this);
        }
    }

    public function ignoreUnmappedProperties(): bool
    {
        return false;
    }
}
