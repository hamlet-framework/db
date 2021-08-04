<?php

namespace Hamlet\Database\Resolvers;

use DateTimeImmutable;
use Hamlet\Database\DatabaseException;
use Hamlet\Database\Entity;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;

class MethodBasedTypeResolverTest extends TestCase
{
    public function testNonStaticResolverRaisesException()
    {
        $object = new class() implements Entity {
            public function __resolveType(): string {
                return stdClass::class;
            }
        };
        $reflectionClass = new ReflectionClass($object);
        $reflectionMethod = $reflectionClass->getMethod('__resolveType');

        $resolver = new MethodBasedTypeResolver(stdClass::class, $reflectionMethod);

        $this->expectException(DatabaseException::class);
        $this->expectDeprecationMessage('Type resolver must be static');
        $resolver->resolveType([]);
    }

    public function testNonPublicResolverRaisesException()
    {
        $object = new class() implements Entity {
            protected static function __resolveType(): string {
                return stdClass::class;
            }
        };
        $reflectionClass = new ReflectionClass($object);
        $reflectionMethod = $reflectionClass->getMethod('__resolveType');

        $resolver = new MethodBasedTypeResolver(stdClass::class, $reflectionMethod);

        $this->expectException(DatabaseException::class);
        $this->expectDeprecationMessage('Type resolver must be public');
        $resolver->resolveType([]);
    }

    public function testInvalidTypeResolverRaisesException()
    {
        $object = new class() implements Entity {
            public static function __resolveType(): int {
                return 1;
            }
        };
        $reflectionClass = new ReflectionClass($object);
        $reflectionMethod = $reflectionClass->getMethod('__resolveType');

        $resolver = new MethodBasedTypeResolver(stdClass::class, $reflectionMethod);

        $this->expectException(DatabaseException::class);
        $this->expectDeprecationMessage('Type resolver must return a string');
        $resolver->resolveType([]);
    }

    public function testInvalidClassNameResolverRaisesException()
    {
        $object = new class() implements Entity {
            public static function __resolveType(): string {
                return 'abc';
            }
        };
        $reflectionClass = new ReflectionClass($object);
        $reflectionMethod = $reflectionClass->getMethod('__resolveType');

        $resolver = new MethodBasedTypeResolver(stdClass::class, $reflectionMethod);

        $this->expectException(DatabaseException::class);
        $this->expectDeprecationMessage('Type resolver must return a valid class-string');
        $resolver->resolveType([]);
    }

    public function testInvalidSubclassResolverRaisesException()
    {
        $object = new class() implements Entity {
            public static function __resolveType(): string {
                return DateTimeImmutable::class;
            }
        };
        $reflectionClass = new ReflectionClass($object);
        $reflectionMethod = $reflectionClass->getMethod('__resolveType');

        $resolver = new MethodBasedTypeResolver(stdClass::class, $reflectionMethod);

        $this->expectException(DatabaseException::class);
        $this->expectDeprecationMessage('Type resolved outside of inheritance tree');
        $resolver->resolveType([]);
    }
}
