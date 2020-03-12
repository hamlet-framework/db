<?php

namespace Hamlet\Database\Processing;

use Generator;
use Hamlet\Database\Traits\SplitterTrait;

/**
 * @template I as array-key
 * @template K as array-key
 * @template V
 *
 * @extends Collector<I,array<K, V>>
 */
class Selector extends Collector
{
    use SplitterTrait;

    /**
     * @param Generator<I,array<K,V>,mixed,void> $records
     * @param bool $streamingMode
     */
    public function __construct(Generator $records, bool $streamingMode)
    {
        parent::__construct($records, $streamingMode);
    }

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
     * @return MapConverter<I,K,V,array-key,V>
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
    public function coalesce(string $field, string ...$fields): Converter
    {
        return new Converter($this->records, $this->coalesceSplitter($field, ...$fields), $this->streamingMode);
    }

    /**
     * @return Collector<int|string,V>
     */
    public function coalesceAll(): Collector
    {
        $generator =
            /**
             * @return Generator<int|string,V,mixed,void>
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
}
