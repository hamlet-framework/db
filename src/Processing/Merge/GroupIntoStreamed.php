<?php declare(strict_types=1);

namespace Hamlet\Database\Processing\Merge;

use Generator;
use Hamlet\Database\Traits\EntityFactoryTrait;

class GroupIntoStreamed
{
    use EntityFactoryTrait;

    public function __construct(private readonly string $name)
    {
    }

    /**
     * @template I as array-key
     * @template K as array-key
     * @template V
     * @template E
     * @param Generator<I,array{E,array<K,V>}> $records
     * @return Generator<I,array<K|string,V|list<E>>>
     */
    public function transform(Generator $records): Generator
    {
        $currentGroup = null;
        $lastRecord = null;
        $lastKey = null;
        foreach ($records as $key => list($item, $record)) {
            if ($lastRecord !== $record) {
                if (!$this->isNull($currentGroup)) {
                    if ($lastRecord === null) {
                        $lastRecord = [];
                    }
                    $lastRecord[$this->name] = $currentGroup;
                    if (!$this->isNull($lastRecord)) {
                        assert(!is_null($lastKey) && !is_null($lastRecord));
                        yield $lastKey => $lastRecord;
                    }
                }
                $lastKey = $key;
                $currentGroup = [];
            }
            if (!$this->isNull($item)) {
                $currentGroup[] = $item;
            }
            $lastRecord = $record;
        }
        $lastRecord[$this->name] = $currentGroup;
        if (!$this->isNull($lastRecord)) {
            assert(!is_null($lastKey) && !is_null($lastRecord));
            yield $lastKey => $lastRecord;
        }
    }
}
