<?php declare(strict_types=1);

namespace Hamlet\Database\Processing\Merge;

use Generator;

class Name
{
    /**
     * @var string
     */
    private $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * @template I as array-key
     * @template K as array-key
     * @template V
     * @template E
     * @param Generator<I,array{E,array<K,V>}> $records
     * @return Generator<I,array<K|string,V|E>>
     */
    public function __invoke(Generator $records): Generator
    {
        foreach ($records as $key => list($item, $record)) {
            $record[$this->name] = $item;
            yield $key => $record;
        }
    }
}
