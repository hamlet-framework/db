<?php declare(strict_types=1);

namespace Hamlet\Database\Processing\Merge;

use Generator;

class Name
{
    public function __construct(private readonly string $name)
    {
    }

    /**
     * @template I as array-key
     * @template K as array-key
     * @template V
     * @template E
     * @param Generator<I,array{E,array<K,V>}> $records
     * @return Generator<I,array<K|string,V|E>>
     */
    public function transform(Generator $records): Generator
    {
        foreach ($records as $key => list($item, $record)) {
            $record[$this->name] = $item;
            yield $key => $record;
        }
    }
}
