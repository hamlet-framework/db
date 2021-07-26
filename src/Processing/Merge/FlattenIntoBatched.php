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
     * @param Generator<I,array{array<K1,V1>,array<K,V>}> $records
     * @return Generator<I,array<K|string,V|array<K1,V1>>>
     */
    public function transform(Generator $records): Generator
    {
        $processedRecords = [];
        $maps = [];
        $keys = [];
        foreach ($records as $key => list($item, $record)) {
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
                assert(is_array($item));
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
