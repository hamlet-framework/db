<?php declare(strict_types=1);

namespace Hamlet\Database\Processing\Merge;

use Generator;
use Hamlet\Database\DatabaseException;
use Hamlet\Database\Traits\EntityFactoryTrait;
use function md5;
use function serialize;

class FlattenIntoBatched
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
     * @template K1 as array-key
     * @template V1
     * @param Generator<I,array<K,V>> $records
     * @param callable(array<K,V>):array{0:array<K1,V1>,1:array<K,V>} $splitter
     * @return Generator<I,array<K|string,V|array<K1,V1>>>
     */
    public function __invoke(Generator $records, callable $splitter): Generator
    {
        $processedRecords = [];
        $maps = [];
        $keys = [];
        foreach ($records as $key => $record) {
            list($item, $record) = $splitter($record);
            $hash = md5(serialize($record));
            if (!isset($keys[$hash])) {
                $keys[$hash] = $key;
            } else {
                $key = $keys[$hash];
            }
            if (!isset($maps[$key])) {
                $maps[$key] = [];
            }
            if (!$this->isNull($item)) {
                if (!is_array($item)) {
                    throw new DatabaseException('Expected array, given ' . var_export($item, true));
                }
                $maps[$key] += $item;
            }
            $processedRecords[$key] = $record;
        }
        foreach ($processedRecords as $key => &$record) {
            $record[$this->name] = $maps[$key];
            yield $key => $record;
        }
    }
}
