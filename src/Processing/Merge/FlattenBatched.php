<?php declare(strict_types=1);

namespace Hamlet\Database\Processing\Merge;

use Generator;
use Hamlet\Database\Traits\EntityFactoryTrait;

class FlattenBatched extends FlattenIntoBatched
{
    use EntityFactoryTrait;

    public function __construct()
    {
        parent::__construct(':property:');
    }

    /**
     * @template I as array-key
     * @template K as array-key
     * @template V
     * @template K1 as array-key
     * @template V1
     * @param Generator<I,array{array<K1,V1>,array<K,V>}> $records
     * @return Generator<K1,V1>
     * @psalm-suppress LessSpecificImplementedReturnType
     */
    public function transform(Generator $records): Generator
    {
        $map = [];
        foreach (parent::transform($records) as $record) {
            assert(array_key_exists(':property:', $record));
            $item = $record[':property:'];
            assert(is_array($item));
            $map += $item;
        }
        yield from $map;
    }
}
