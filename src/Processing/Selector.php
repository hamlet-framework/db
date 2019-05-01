<?php

namespace Hamlet\Database\Processing;

use function assert;
use Generator;
use Hamlet\Database\Traits\SplitterTrait;
use function is_int;
use function is_string;

/**
 * @template I as int|string
 * @template K as int|string
 * @template V
 *
 * @extends Collector<I, array<K, V>>
 */
class Selector extends Collector
{
    use SplitterTrait;

    /**
     * @param Generator $records
     * @psalm-param Generator<I, array<K, V>, mixed, void> $records
     * @param bool $streamingMode
     */
    public function __construct(Generator $records, bool $streamingMode)
    {
        parent::__construct($records, $streamingMode);
    }

    /**
     * @param string $field
     * @return Converter
     * @psalm-return Converter<I, K, V, V>
     */
    public function selectValue(string $field): Converter
    {
        return new Converter($this->records, $this->selectValueSplitter($field), $this->streamingMode);
    }

    /**
     * @param string $field
     * @param string ...$fields
     * @return Converter
     * @psalm-return Converter<I,K,V,array<K,V>>
     */
    public function selectFields(string $field, string ...$fields): Converter
    {
        return new Converter($this->records, $this->selectFieldsSplitter($field, ...$fields), $this->streamingMode);
    }

    /**
     * @param string $keyField
     * @param string $valueField
     * @return MapConverter
     * @psalm-return MapConverter<I,K,V,int|string|float|null,V>
     */
    public function map(string $keyField, string $valueField): MapConverter
    {
        return new MapConverter($this->records, $this->mapSplitter($keyField, $valueField), $this->streamingMode);
    }

    /**
     * @param string $prefix
     * @return Converter
     * @psalm-return Converter<I,K,V,array<string,V>>
     */
    public function selectByPrefix(string $prefix): Converter
    {
        return new Converter($this->records, $this->selectByPrefixSplitter($prefix), $this->streamingMode);
    }

    /**
     * @return Converter
     * @psalm-return Converter<I,K,V,array<K,V>>
     */
    public function selectAll(): Converter
    {
        return new Converter($this->records, $this->selectAllSplitter(), $this->streamingMode);
    }

    /**
     * @param string $field
     * @param string ...$fields
     * @return Converter
     * @psalm-return Converter<I,K,V,V|null>
     */
    public function coalesce(string $field, string... $fields): Converter
    {
        return new Converter($this->records, $this->coalesceSplitter($field, ...$fields), $this->streamingMode);
    }

    /**
     * @return Collector
     * @psalm-return Collector<I,V>
     */
    public function coalesceAll(): Collector
    {
        $generator =
            /**
             * @return Generator
             * @psalm-return Generator<I,V,mixed,void>
             */
            function (): Generator {
                foreach ($this->records as $key => $record) {
                    foreach ($record as &$value) {
                        if ($value !== null) {
                            yield $key => $value;
                            break;
                        }
                    }
                }
            };

        return new Collector($generator(), $this->streamingMode);
    }

    /**
     * @param string $keyField
     * @return Collector
     * @psalm-return Collector<int|string,array<K,V>>
     *
     * @todo it would be nice to have an intersection type Collector<V & array-key, array<K, V>>
     */
    public function withKey(string $keyField): Collector
    {
        $generator =
            /**
             * @return Generator
             * @psalm-return Generator<int|string, array<K, V>, mixed, void>
             */
            function () use ($keyField): Generator {
                foreach ($this->records as &$record) {
                    $key = $record[$keyField];
                    if ($key === null) {
                        continue;
                    }
                    assert(is_int($key) || is_string($key));
                    yield $key => $record;
                }
            };

        return new Collector($generator(), $this->streamingMode);
    }
}
