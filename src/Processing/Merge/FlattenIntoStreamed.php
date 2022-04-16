<?php declare(strict_types=1);

namespace Hamlet\Database\Processing\Merge;

use Generator;
use Hamlet\Database\Traits\EntityFactoryTrait;

class FlattenIntoStreamed
{
    use EntityFactoryTrait;

    public function __construct(private readonly string $name)
    {
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
        /** @var array<K1,V1>|null $currentGroup */
        $currentGroup = null;

        /** @var array<K,V>|null $lastRecord */
        $lastRecord = null;

        /** @var I|null $lastKey */
        $lastKey = null;

        foreach ($records as $key => list($item, $record)) {
            if ($lastRecord !== $record) {
                if ($currentGroup !== null) {
                    if ($lastRecord === null) {
                        $lastRecord = [];
                    }
                    $lastRecord[$this->name] = $currentGroup;
                    if (!$this->isNull($lastRecord)) {
                        assert(!is_null($lastKey));
                        yield $lastKey => $lastRecord;
                    }
                }
                $lastKey = $key;
                $currentGroup = [];
            }
            if (!$this->isNull($item)) {
                if ($currentGroup === null) {
                    $currentGroup = [];
                }
                assert(is_array($item));
                $currentGroup += $item;
            }
            $lastRecord = $record;
        }
        $lastRecord[$this->name] = $currentGroup;
        if (!$this->isNull($lastRecord)) {
            assert(!is_null($lastKey));
            yield $lastKey => $lastRecord;
        }
    }
}
