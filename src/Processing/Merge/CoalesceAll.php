<?php

namespace Hamlet\Database\Processing\Merge;

use Generator;

class CoalesceAll
{
    /**
     * @template I as array-key
     * @template K as array-key
     * @template V
     * @param Generator<I,array<K,V>> $records $records
     * @return Generator<int|string,V>
     */
    public function __invoke(Generator $records): Generator
    {
        foreach ($records as $key => $record) {
            foreach ($record as &$value) {
                if ($value !== null) {
                    yield $key => $value;
                    break;
                }
            }
        }
    }
}
