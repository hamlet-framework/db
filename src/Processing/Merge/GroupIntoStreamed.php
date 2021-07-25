<?php declare(strict_types=1);

namespace Hamlet\Database\Processing\Merge;

use Generator;
use Hamlet\Database\Traits\EntityFactoryTrait;

class GroupIntoStreamed
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
        $currentGroup = null;
        $lastRecord = null;
        $lastKey = null;
        foreach ($records as $key => $record) {
            list($item, $record) = $splitter($record);
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
