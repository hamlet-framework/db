<?php declare(strict_types=1);

namespace Hamlet\Database\Processing\Merge;

use Generator;
use Hamlet\Cast\ClassType;
use Hamlet\Database\Resolvers\EntityResolver;

/**
 * @template Q
 */
class CastInto
{
    /**
     * @var class-string<Q>
     */
    protected $typeName;

    /**
     * @var string
     */
    private $name;

    /**
     * @param class-string<Q> $typeName
     * @param string $name
     */
    public function __construct(string $typeName, string $name)
    {
        $this->typeName = $typeName;
        $this->name = $name;
    }

    /**
     * @template I as array-key
     * @template K as array-key
     * @template V
     * @template E
     * @param Generator<I,array{E,array<K,V>}> $records
     * @return Generator<I,array<K|string,V|Q>>
     */
    public function __invoke(Generator $records): Generator
    {
        $type = new ClassType($this->typeName);
        $entityResolver = new EntityResolver;
        foreach ($records as $key => list($item, $record)) {
            $instance = $type->resolveAndCast($item, $entityResolver);
            $record[$this->name] = $instance;
            yield $key => $record;
        }
    }
}
