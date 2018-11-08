<?php

namespace Hamlet\Database\Processing;

use Hamlet\Database\Traits\SplitterTrait;

/**
 * @template I
 * @template K
 * @template V
 *
 * @template-extends Collector<I,array<K,V>>
 */
class Selector extends Collector
{
    /**
     * @template-use SplitterTrait<K,V>
     */
    use SplitterTrait;

    /**
     * @param string $field
     * @return Converter<I,K,V,V>
     */
    public function selectValue(string $field): Converter
    {
        return new Converter($this->records, $this->selectValueSplitter($field), $this->streamingMode);
    }

    /**
     * @param string $field
     * @param string ...$fields
     * @return Converter<I,K,V,array<K,V>>
     */
    public function selectFields(string $field, string ...$fields): Converter
    {
        return new Converter($this->records, $this->selectFieldsSplitter($field, ...$fields), $this->streamingMode);
    }

    /**
     * @param string $keyField
     * @param string $valueField
     * @return MapConverter<I,K,V,array<int|string|float,V>>
     */
    public function map(string $keyField, string $valueField): MapConverter
    {
        return new MapConverter($this->records, $this->mapSplitter($keyField, $valueField), $this->streamingMode);
    }

    /**
     * @param string $prefix
     * @return Converter<I,K,V,array<string,V>>
     */
    public function selectByPrefix(string $prefix): Converter
    {
        return new Converter($this->records, $this->selectByPrefixSplitter($prefix), $this->streamingMode);
    }

    /**
     * @return Converter<I,K,V,array<K,V>>
     */
    public function selectAll(): Converter
    {
        return new Converter($this->records, $this->selectAllSplitter(), $this->streamingMode);
    }

    /**
     * @param string $field
     * @param string ...$fields
     * @return Converter<I,K,V,V|null>
     */
    public function coalesce(string $field, string... $fields): Converter
    {
        return new Converter($this->records, $this->coalesceSplitter($field, ...$fields), $this->streamingMode);
    }

    /**
     * @return Collector<I,V>
     */
    public function coalesceAll(): Collector
    {
        $generator = function () {
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
     * @return Collector<int|string|float,V>
     */
    public function withKey(string $keyField): Collector
    {
        $generator = function () use ($keyField) {
            foreach ($this->records as &$record) {
                $key = $record[$keyField];
                if ($key === null) {
                    continue;
                }
                \assert(\is_int($key) || \is_string($key) || \is_float($key));
                yield $key => $record;
            }
        };

        return new Collector($generator(), $this->streamingMode);
    }
}
