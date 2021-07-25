<?php declare(strict_types=1);

namespace Hamlet\Database\Processing\Merge;

use Generator;
use Hamlet\Database\Traits\EntityFactoryTrait;
use function md5;
use function serialize;

class GroupIntoBatched
{
    use EntityFactoryTrait;

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
     * @param Generator<I,array<K,V>> $records
     * @param callable(array<K,V>):array{0:E,1:array<K,V>} $splitter
     * @return Generator<I,array<K|string,V|list<E>>>
     */
    public function __invoke(Generator $records, callable $splitter): Generator
    {
        $processedRecords = [];
        $groups = [];
        $keys = [];

        foreach ($records as $key => $record) {
            list($item, $record) = $splitter($record);
            $hash = md5(serialize($record));
            if (!isset($keys[$hash])) {
                $keys[$hash] = $key;
            } else {
                $key = $keys[$hash];
            }
            if (!isset($groups[$key])) {
                $groups[$key] = [];
            }
            if (!$this->isNull($item)) {
                $groups[$key][] = $item;
            }
            $processedRecords[$key] = $record;
        }

        foreach ($processedRecords as $key => &$record) {
            $record[$this->name] = $groups[$key];
            yield $key => $record;
        }
    }
}
