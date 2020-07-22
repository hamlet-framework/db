<?php declare(strict_types=1);

namespace Hamlet\Database\Processing;

use Generator;
use Hamlet\Database\DatabaseException;
use Hamlet\Database\Traits\EntityFactoryTrait;
use function assert;
use function is_null;
use function md5;
use function serialize;

/**
 * I  - Index type of all records
 * K  - Key type of the original record
 * V  - Value type of the original record
 * K1 - Key type of the extracted record
 * V1 - Value type of the extracted record
 *
 * @template I as array-key
 * @template K as array-key
 * @template V
 * @template K1 as array-key
 * @template V1
 *
 * @extends Converter<I,K,V,array<K1,V1>>
 */
class MapConverter extends Converter
{
    use EntityFactoryTrait;

    /**
     * @param Generator<I,array<K,V>,mixed,void> $records
     * @param callable(array<K,V>):array{0:array<K1,V1>,1:array<K,V>} $splitter
     * @param bool $streamingMode
     */
    public function __construct(Generator $records, callable $splitter, bool $streamingMode)
    {
        parent::__construct($records, $splitter, $streamingMode);
    }

    /**
     * @return Collector<K1,V1>
     */
    public function flatten(): Collector
    {
        $generator =
            /**
             * @return Generator<K1,V1,mixed,void>
             * @psalm-suppress InvalidReturnType
             */
            function () {
                $map = [];
                foreach ($this->flattenRecordsInto(':property:') as $record) {
                    $item = $record[':property:'];
                    if (!is_array($item)) {
                        throw new DatabaseException('Expected array, given: ' . var_export($item, true));
                    }
                    $map += $item;
                }
                yield from $map;
            };
        return new Collector($generator(), $this->streamingMode);
    }

    /**
     * @param string $name
     * @return Selector<I,K|string,array<K1,V1>|V>
     */
    public function flattenInto(string $name): Selector
    {
        return new Selector($this->flattenRecordsInto($name), $this->streamingMode);
    }

    /**
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
     * @psalm-suppress MixedReturnTypeCoercion
     */
    private function flattenRecordsStreamingMode(Generator $generator, string $name): Generator
    {
        /** @var array<K1,V1>|null $currentGroup */
        $currentGroup = null;

        /** @var array<K,V>|null $lastRecord */
        $lastRecord = null;

        /** @var I|null $lastKey */
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
        $lastRecord[$name] = $currentGroup;
        if (!$this->isNull($lastRecord)) {
            assert(!is_null($lastKey));
            yield $lastKey => $lastRecord;
        }
    }

    /**
     * @param Generator<I,array<K,V>> $generator
     * @param string $name
     * @return Generator<I,array<K|string,V|array<K1,V1>>,mixed,void>
     * @psalm-suppress MixedReturnTypeCoercion
     */
    private function flattenRecordsBatchMode(Generator $generator, string $name): Generator
    {
        $records = [];
        $maps = [];
        $keys = [];
        foreach ($generator as $key => $record) {
            list($item, $record) = ($this->splitter)($record);
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
            $records[$key] = $record;
        }
        foreach ($records as $key => &$record) {
            $record[$name] = $maps[$key];
            yield $key => $record;
        }
    }
}
