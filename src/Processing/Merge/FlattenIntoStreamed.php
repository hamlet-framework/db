<?php declare(strict_types=1);

namespace Hamlet\Database\Processing\Merge;

use Generator;
use Hamlet\Database\DatabaseException;
use Hamlet\Database\Traits\EntityFactoryTrait;

class FlattenIntoStreamed
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
        /** @var array<K1,V1>|null $currentGroup */
        $currentGroup = null;

        /** @var array<K,V>|null $lastRecord */
        $lastRecord = null;

        /** @var I|null $lastKey */
        $lastKey = null;

        foreach ($records as $key => $record) {
            list($item, $record) = $splitter($record);
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
                if (!is_array($item)) {
                    throw new DatabaseException('Expected array, given: ' . var_export($item, true));
                }
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
