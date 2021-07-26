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
 * @extends Collection<I,array<K,V>>
 */
class SplitContext extends Collection
{
    /**
     * @param Generator<I,array<K,V>> $source
     * @param bool $streamingMode
     */
    public function __construct(Generator $source, bool $streamingMode)
    {
        parent::__construct($source, $streamingMode);
    }

    /**
     * @param string $field
     * @return MergeContext<I,K,V,V>
     */
    public function selectValue(string $field): MergeContext
    {
        $splitter = new SelectValue($field);
        return new MergeContext($splitter->transform($this->source), $this->streamingMode);
    }

    /**
     * @param string $field
     * @param string ...$fields
     * @return MergeContext<I,K,V,array<K,V>>
     */
    public function selectFields(string $field, string ...$fields): MergeContext
    {
        array_unshift($fields, $field);
        $splitter = new SelectFields($fields);
        return new MergeContext($splitter->transform($this->source), $this->streamingMode);
    }

    /**
     * @param string $keyField
     * @param string $valueField
     * @return MapMergeContext<I,K,V,array-key,V>
     */
    public function map(string $keyField, string $valueField): MapMergeContext
    {
        $splitter = new Map($keyField, $valueField);
        return new MapMergeContext($splitter->transform($this->source), $this->streamingMode);
    }

    /**
     * @param string $prefix
     * @return MergeContext<I,K,V,array<string,V>>
     */
    public function selectByPrefix(string $prefix): MergeContext
    {
        $splitter = new SelectByPrefix($prefix);
        return new MergeContext($splitter->transform($this->source), $this->streamingMode);
    }

    /**
     * @return MergeContext<I,K,V,array<K,V>>
     */
    public function selectAll(): MergeContext
    {
        $splitter = new SelectAll;
        return new MergeContext($splitter->transform($this->source), $this->streamingMode);
    }

    /**
     * @param string $field
     * @param string ...$fields
     * @return MergeContext<I,K,V,V|null>
     */
    public function coalesce(string $field, string ...$fields): MergeContext
    {
        array_unshift($fields, $field);
        $splitter = new Coalesce($fields);
        return new MergeContext($splitter->transform($this->source), $this->streamingMode);
    }

    /**
     * @return Collection<I,V>
     */
    public function coalesceAll(): Collection
    {
        $converter = new CoalesceAll;
        return new Collection($converter->transform($this->source), $this->streamingMode);
    }
}
