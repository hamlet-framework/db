<?php declare(strict_types=1);

namespace Hamlet\Database\Processing\Merge;

use Generator;
use Hamlet\Cast\ClassType;

/**
 * @template Q
 * @extends CastInto<Q>
 */
class Cast extends CastInto
{
    /**
     * @param class-string<Q> $typeName
     */
    public function __construct(string $typeName)
    {
        parent::__construct($typeName, ':property:');
    }

    /**
     * @template I as array-key
     * @template K as array-key
     * @template V
     * @template E
     * @param Generator<I,array{E,array<K,V>}> $records
     * @return Generator<I,Q>
     */
    public function __invoke(Generator $records): Generator
    {
        foreach (parent::__invoke($records) as $key => $record) {
            $value = $record[':property:'];
            assert(($type = new ClassType($this->typeName)) && $type->matches($value));
            yield $key => $value;
        }
    }
}
