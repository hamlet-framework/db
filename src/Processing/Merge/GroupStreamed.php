<?php declare(strict_types=1);

namespace Hamlet\Database\Processing\Merge;

use Generator;
use Hamlet\Database\Traits\EntityFactoryTrait;

class GroupStreamed extends GroupIntoStreamed
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
     * @param Generator<I,array<K,V>> $records
     * @param callable(array<K,V>):array{0:E,1:array<K,V>} $splitter
     * @return Generator<I,array<I,E>|V>
     */
    public function __invoke(Generator $records, callable $splitter): Generator
    {
        foreach (parent::__invoke($records, $splitter) as $key => $record) {
            $value = $record[':property:'];
            // @todo can we check the type somehow?
            yield $key => $value;
        }
    }
}
