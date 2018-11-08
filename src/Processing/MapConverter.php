<?php

namespace Hamlet\Database\Processing;

use Generator;
use Hamlet\Database\Traits\EntityFactoryTrait;

/**
 * @template I
 * @template K
 * @template V
 * @template K1
 * @template V1
 *
 * @template-extends Converter<I,K,V,array<K1,V1>>
 */
class MapConverter extends Converter
{
    use EntityFactoryTrait;

    /**
     * @return Collector<K1,V1>
     */
    public function flatten(): Collector
    {
        $generator = function () {
            $map = [];
            foreach ($this->flattenRecordsInto(':property:') as $record) {
                $map += $record[':property:'];
            }
            yield from $map;
        };
        return new Collector($generator(), $this->streamingMode);
    }

    /**
     * @param string $name
     * @return Selector<I,array<K|string,V|array<K1,V1>>>
     */
    public function flattenInto(string $name): Selector
    {
        return new Selector($this->flattenRecordsInto($name), $this->streamingMode);
    }

    /**
     * @param Generator<I,array<K,V>> $generator
     * @param string $name
     * @return Generator<I,array<K|string,V|array<K1,V1>>,mixed,void>
     */
    private function flattenRecordsInto(string $name): Generator
    {
        if ($this->streamingMode) {
            return $this->flattenRecordsStreamingMode($this->records, $name);
        } else {
            return $this->flattenRecordsBatchMode($this->records, $name);
        }
    }

    /**
     * @param Generator<I,array<K,V>> $generator
     * @param string $name
     * @return Generator<I,array<K|string,V|array<K1,V1>>,mixed,void>
     *
     *
     * @todo fix the logic
     */
    private function flattenRecordsStreamingMode(Generator $generator, string $name): Generator
    {
        $currentGroup = null;
        $lastRecord = null;
        $lastKey = null;
        foreach ($generator as $key => $record) {
            list($item, $record) = ($this->splitter)($record);
            if ($lastRecord !== $record) {
                if ($currentGroup !== null) {
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
                if ($currentGroup === null) {
                    $currentGroup = [];
                }
                $currentGroup += $item;
            }
            $lastRecord = $record;
        }
        $lastRecord[$name] = $currentGroup;
        if (!$this->isNull($lastRecord)) {
            yield $lastKey => $lastRecord;
        }
    }

    /**
     * @param Generator<I,array<K,V>> $generator
     * @param string $name
     * @return Generator<I,array<K|string,V|array<K1,V1>>,mixed,void>
     */
    private function flattenRecordsBatchMode(Generator $generator, string $name): Generator
    {
        $records = [];
        $maps = [];
        $keys = [];
        foreach ($generator as $key => $record) {
            list($item, $record) = ($this->splitter)($record);
            $hash = \md5(\serialize($record));
            if (!isset($keys[$hash])) {
                $keys[$hash] = $key;
            } else {
                $key = $keys[$hash];
            }
            if (!isset($maps[$key])) {
                $maps[$key] = [];
            }
            if (!$this->isNull($item)) {
                $maps[$key] += $item;
            }
            $records[$key] = $record;
        }
        foreach ($records as $key => &$record) {
            $record[$name] = $maps[$key];
            yield $key => $record;
        }
    }
}
