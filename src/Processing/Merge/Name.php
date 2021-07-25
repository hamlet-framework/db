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
     * @param Generator<I,array<K,V>,mixed,void> $records
     * @param callable(array<K,V>):array{0:E,1:array<K,V>} $splitter
     * @return Generator<I,array<K|string,V|E>>
     */
    public function __invoke(Generator $records, callable $splitter): Generator
    {
        foreach ($records as $key => $record) {
            list($item, $record) = ($splitter)($record);
            $record[$this->name] = $item;
            yield $key => $record;
        }
    }
}
