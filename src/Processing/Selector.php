<?php declare(strict_types=1);

namespace Hamlet\Database\Processing;

use Generator;
use Hamlet\Database\Processing\Merge\CoalesceAll;
use Hamlet\Database\Processing\Split\Coalesce;
use Hamlet\Database\Processing\Split\Map;
use Hamlet\Database\Processing\Split\SelectAll;
use Hamlet\Database\Processing\Split\SelectByPrefix;
use Hamlet\Database\Processing\Split\SelectFields;
use Hamlet\Database\Processing\Split\SelectValue;

/**
 * @template I as array-key
 * @template K as array-key
 * @template V
 *
 * @extends Collector<I,array<K, V>>
 */
class Selector extends Collection
{
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
        $splitter = new SelectValue($field);
        return new Converter($this->source, $splitter, $this->streamingMode);
    }

    /**
     * @param string $field
     * @param string ...$fields
     * @return Converter<I,K,V,array<K,V>>
     */
    public function selectFields(string $field, string ...$fields): Converter
    {
        array_unshift($fields, $field);
        $splitter = new SelectFields($fields);
        return new Converter($this->source, $splitter, $this->streamingMode);
    }

    /**
     * @param string $keyField
     * @param string $valueField
     * @return MapConverter<I,K,V,array-key,V>
     */
    public function map(string $keyField, string $valueField): MapConverter
    {
        $splitter = new Map($keyField, $valueField);
        return new MapConverter($this->source, $splitter, $this->streamingMode);
    }

    /**
     * @param string $prefix
     * @return Converter<I,K,V,array<string,V>>
     */
    public function selectByPrefix(string $prefix): Converter
    {
        $splitter = new SelectByPrefix($prefix);
        return new Converter($this->source, $splitter, $this->streamingMode);
    }

    /**
     * @return Converter<I,K,V,array<K,V>>
     */
    public function selectAll(): Converter
    {
        $splitter = new SelectAll;
        return new Converter($this->source, $splitter, $this->streamingMode);
    }

    /**
     * @param string $field
     * @param string ...$fields
     * @return Converter<I,K,V,V|null>
     */
    public function coalesce(string $field, string ...$fields): Converter
    {
        array_unshift($fields, $field);
        $splitter = new Coalesce($fields);
        return new Converter($this->source, $splitter, $this->streamingMode);
    }

    /**
     * @return Collection<int|string,V>
     */
    public function coalesceAll(): Collection
    {
        $generator = (new CoalesceAll)($this->source);
        return new Collection($generator, $this->streamingMode);
    }
}
