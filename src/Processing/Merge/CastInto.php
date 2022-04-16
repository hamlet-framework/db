<?php declare(strict_types=1);

namespace Hamlet\Database\Processing\Merge;

use Generator;
use Hamlet\Cast\ClassType;
use Hamlet\Database\DatabaseException;
use Hamlet\Database\Resolvers\EntityResolver;
use ReflectionException;

/**
 * @template Q as object
 */
class CastInto
{
    /**
     * @param class-string<Q> $typeName
     * @param string $name
     */
    public function __construct(protected readonly string $typeName, private readonly string $name)
    {
    }

    /**
     * @template I as array-key
     * @template K as array-key
     * @template V
     * @template E
     * @param Generator<I,array{E,array<K,V>}> $records
     * @return Generator<I,array<K|string,V|Q>>
     */
    public function transform(Generator $records): Generator
    {
        $type = new ClassType($this->typeName);
        $entityResolver = new EntityResolver;
        foreach ($records as $key => list($item, $record)) {
            try {
                $instance = $type->resolveAndCast($item, $entityResolver);
            } catch (ReflectionException $exception) {
                throw new DatabaseException('Cannot transform record', 0, $exception);
            }
            $record[$this->name] = $instance;
            yield $key => $record;
        }
    }
}
