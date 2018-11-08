<?php

namespace Hamlet\Database\Processing;

use Generator;
use Hamlet\Database\Traits\EntityFactoryTrait;
use Traversable;

/**
 * @template I
 * @template K
 * @template V
 * @template E
 */
class Converter
{
    use EntityFactoryTrait;

    /**
     * @var Generator<I,array<K,V>,mixed,void>
     */
    protected $records;

    /**
     * @var callable(array<K,V>):array{0:E,1:array<K,V>}
     */
    protected $splitter;

    /**
     * @var bool
     */
    protected $streamingMode;

    /**
     * @param Generator<I,array<K,V>,mixed,void> $records
     * @param callable(array<K,V>):array{0:E,1:array<K,V>} $splitter
     * @param bool $streamingMode
     */
    public function __construct(Generator $records, callable $splitter, bool $streamingMode)
    {
        $this->records = $records;
        $this->splitter = $splitter;
        $this->streamingMode = $streamingMode;
    }

    /**
     * @param string $name
     * @return Selector<I,K|string,V|E>
     */
    public function name(string $name): Selector
    {
        $generator = function () use ($name) {
            foreach ($this->records as $key => $record) {
                list($item, $record) = ($this->splitter)($record);
                $record[$name] = $item;
                yield $key => $record;
            }
        };
        return new Selector($generator(), $this->streamingMode);
    }

    /**
     * @return Collector<I,array<I>>
     */
    public function group(): Collector
    {
        $generator = function () {
            foreach ($this->groupRecordsInto(':property:') as $key => $record) {
                yield $key => $record[':property:'];
            }
        };
        return new Collector($generator(), $this->streamingMode);
    }

    /**
     * @param string $name
     * @return Selector<I,K|string,V|array<E>>
     */
    public function groupInto(string $name): Selector
    {
        return new Selector($this->groupRecordsInto($name), $this->streamingMode);
    }

    /**
     * @param string $name
     * @return Generator<I,array<K|string,V|array<E>>,mixed,void>
     */
    private function groupRecordsInto(string $name): Generator
    {
        if ($this->streamingMode) {
            return $this->groupRecordsStreamingMode($this->records, $name);
        } else {
            return $this->groupRecordsBatchMode($this->records, $name);
        }
    }

    /**
     * @param Generator<I,array<K,V>,mixed,null>
     * @param string $name
     * @return Generator<I,array<K|string,V|array<E>>,mixed,void>
     */
    private function groupRecordsBatchMode(Generator $generator, string $name): Generator
    {
        $records = [];
        $groups = [];
        $keys = [];
        foreach ($generator as $key => $record) {
            list($item, $record) = ($this->splitter)($record);
            $hash = \md5(\serialize($record));
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
            $records[$key] = $record;
        }

        foreach ($records as $key => &$record) {
            $record[$name] = $groups[$key];
            yield $key => $record;
        }
    }

    /**
     * @param Generator<I,array<K,V>,mixed,null>
     * @param string $name
     * @return Generator<I,array<K|string,V|array<E>>,mixed,void>
     */
    private function groupRecordsStreamingMode(Generator $generator, string $name): Generator
    {
        $currentGroup = null;
        $lastRecord = null;
        $lastKey = null;
        foreach ($generator as $key => $record) {
            list($item, $record) = ($this->splitter)($record);
            if ($lastRecord !== $record) {
                if (!$this->isNull($currentGroup)) {
                    if ($lastRecord === null) {
                        $lastRecord = [];
                    }
                    $lastRecord[$name] = $currentGroup;
                    if (!$this->isNull($lastRecord)) {
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
        $lastRecord[$name] = $currentGroup;
        if (!$this->isNull($lastRecord)) {
            yield $lastKey => $lastRecord;
        }
    }

    /**
     * @param string $typeName
     * @return Collector<I,Q>
     */
    public function cast(string $typeName): Collector
    {
        $generator = function () use ($typeName) {
            foreach ($this->castRecordsInto($typeName, ':property:') as $key => $record) {
                yield $key => $record[':property:'];
            }
        };
        return new Collector($generator(), $this->streamingMode);
    }

    /**
     * @param string $typeName
     * @param string $name
     * @return Selector<I,K|string,V|Q>
     */
    public function castInto(string $typeName, string $name): Selector
    {
        return new Selector($this->castRecordsInto($typeName, $name), $this->streamingMode);
    }

    /**
     * @template Q
     * @template-typeof Q $typeName
     * @param string $name
     * @return Generator<I,array<K|string,V|Q>,mixed,void>
     */
    private function castRecordsInto(string $typeName, string $name): Generator
    {
        foreach ($this->records as $key => $record) {
            list($item, $record) = ($this->splitter)($record);
            $record[$name] = $this->instantiate($typeName, $item);
            yield $key => $record;
        }
    }
}
