<?php declare(strict_types=1);

namespace Hamlet\Database\Processing;

use Generator;
use Hamlet\Database\Processing\Merge\FlattenBatched;
use Hamlet\Database\Processing\Merge\FlattenIntoBatched;
use Hamlet\Database\Processing\Merge\FlattenIntoStreamed;
use Hamlet\Database\Processing\Merge\FlattenStreamed;
use Hamlet\Database\Traits\EntityFactoryTrait;

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
     * @return Collection<K1,V1>
     */
    public function flatten(): Collection
    {
        if ($this->streamingMode) {
            $generator = (new FlattenStreamed)($this->records, $this->splitter);
        } else {
            $generator = (new FlattenBatched)($this->records, $this->splitter);
        }
        return new Collection($generator, $this->streamingMode);
    }

    /**
     * @param string $name
     * @return Selector<I,K|string,array<K1,V1>|V>
     */
    public function flattenInto(string $name): Selector
    {
        if ($this->streamingMode) {
            $generator = (new FlattenIntoStreamed($name))($this->records, $this->splitter);
        } else {
            $generator = (new FlattenIntoBatched($name))($this->records, $this->splitter);
        }
        return new Selector($generator, $this->streamingMode);
    }
}
