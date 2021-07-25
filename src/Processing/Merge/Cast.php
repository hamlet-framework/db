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
     * @param Generator<I,array<K,V>> $records
     * @param callable(array<K,V>):array{0:E,1:array<K,V>} $splitter
     * @return Generator<I,E>
     */
    public function __invoke(Generator $records, callable $splitter): Generator
    {
        foreach (parent::__invoke($records, $splitter) as $key => $record) {
            $value = $record[':property:'];
            assert(($type = new ClassType($this->typeName)) && $type->matches($value));
            yield $key => $value;
        }
    }
}
