<?php declare(strict_types=1);

namespace Hamlet\Database\Processing\Merge;

use Generator;
use Hamlet\Database\DatabaseException;
use Hamlet\Database\Traits\EntityFactoryTrait;

class FlattenStreamed extends FlattenIntoBatched
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
     * @param Generator<I,array<K,V>> $records
     * @param callable(array<K,V>):array{0:array<K1,V1>,1:array<K,V>} $splitter
     * @return Generator<K1,V1>
     */
    public function __invoke(Generator $records, callable $splitter): Generator
    {
        $map = [];
        foreach (parent::__invoke($records, $splitter) as $record) {
            $item = $record[':property:'];
            if (!is_array($item)) {
                throw new DatabaseException('Expected array, given: ' . var_export($item, true));
            }
            $map += $item;
        }
        yield from $map;
    }
}
