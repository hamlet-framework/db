<?php declare(strict_types=1);

namespace Hamlet\Database\Processing\Split;

use Generator;

class SelectAll
{
    /**
     * @template K as array-key
     * @template V
     * @param array<K,V> $record
     * @return array{array<K,V>,array<K,V>}
     */
    public function apply(array $record): array
    {
        return [$record, []];
    }

    /**
     * @template I as array-key
     * @template K as array-key
     * @template V
     * @param Generator<I,array<K,V>> $source
     * @return Generator<I,array{array<K,V>,array<K,V>}>
     */
    public function transform(Generator $source): Generator
    {
        foreach ($source as $key => $record) {
            yield $key => $this->apply($record);
        }
    }
}
