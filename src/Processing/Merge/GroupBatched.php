<?php declare(strict_types=1);

namespace Hamlet\Database\Processing\Merge;

use Generator;
use Hamlet\Database\Traits\EntityFactoryTrait;

class GroupBatched extends GroupIntoBatched
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
     * @template E
     * @param Generator<I,array{E,array<K,V>}> $records
     * @return Generator<I,list<E>>
     */
    public function __invoke(Generator $records): Generator
    {
        foreach (parent::__invoke($records) as $key => $record) {
            $value = $record[':property:'];
            // @todo can we check the type somehow?
            yield $key => $value;
        }
    }
}
