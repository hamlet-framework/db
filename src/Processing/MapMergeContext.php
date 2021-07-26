<?php declare(strict_types=1);

namespace Hamlet\Database\Processing;

use Generator;
use Hamlet\Database\Processing\Merge\FlattenBatched;
use Hamlet\Database\Processing\Merge\FlattenIntoBatched;
use Hamlet\Database\Processing\Merge\FlattenIntoStreamed;
use Hamlet\Database\Processing\Merge\FlattenStreamed;
use Hamlet\Database\Traits\EntityFactoryTrait;

/**
 * @template I as array-key
 * @template K as array-key
 * @template V
 * @template K1 as array-key
 * @template V1
 *
 * @extends MergeContext<I,K,V,array<K1,V1>>
 */
class MapMergeContext extends MergeContext
{
    use EntityFactoryTrait;

    /**
     * @param Generator<I,array{array<K1,V1>,array<K,V>}> $records
     * @param bool $streamingMode
     */
    public function __construct(Generator $records, bool $streamingMode)
    {
        parent::__construct($records, $streamingMode);
    }

    /**
     * @return Collection<K1,V1>
     */
    public function flatten(): Collection
    {
        if ($this->streamingMode) {
            $generator = (new FlattenStreamed)($this->source);
        } else {
            $generator = (new FlattenBatched)($this->source);
        }
        return new Collection($generator, $this->streamingMode);
    }

    /**
     * @param string $name
     * @return SplitContext<I,K|string,array<K1,V1>|V>
     */
    public function flattenInto(string $name): SplitContext
    {
        if ($this->streamingMode) {
            $generator = (new FlattenIntoStreamed($name))($this->source);
        } else {
            $generator = (new FlattenIntoBatched($name))($this->source);
        }
        return new SplitContext($generator, $this->streamingMode);
    }
}
