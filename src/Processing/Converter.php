<?php declare(strict_types=1);

namespace Hamlet\Database\Processing;

use Generator;
use Hamlet\Database\Processing\Merge\Cast;
use Hamlet\Database\Processing\Merge\CastInto;
use Hamlet\Database\Processing\Merge\GroupBatched;
use Hamlet\Database\Processing\Merge\GroupIntoBatched;
use Hamlet\Database\Processing\Merge\GroupIntoStreamed;
use Hamlet\Database\Processing\Merge\GroupStreamed;
use Hamlet\Database\Processing\Merge\Name;
use Hamlet\Database\Traits\EntityFactoryTrait;

/**
 * I - Index type of all records
 * K - Key type of original record
 * V - Value type of original record
 * E - Type of element extracted by splitter
 *
 * @template I as array-key
 * @template K as array-key
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
        $generator = (new Name($name))($this->records, $this->splitter);
        return new Selector($generator, $this->streamingMode);
    }

    /**
     * @return Collection<I,array<I,E>|V>
     */
    public function group(): Collection
    {
        if ($this->streamingMode) {
            $generator = (new GroupStreamed)($this->records, $this->splitter);
        } else {
            $generator = (new GroupBatched)($this->records, $this->splitter);
        }
        return new Collection($generator, $this->streamingMode);
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
     * @param Generator<I,array<K,V>,mixed,void> $generator
     * @param string $name
     * @return Generator<I,array<K|string,V|list<E>>,mixed,void>
     */
    private function groupRecordsBatchMode(Generator $generator, string $name): Generator
    {
        return (new GroupIntoBatched($name))($generator, $this->splitter);
    }

    /**
     * @param Generator<I,array<K,V>,mixed,void> $generator
     * @param string $name
     * @return Generator<I,array<K|string,V|list<E>>,mixed,void>
     */
    private function groupRecordsStreamingMode(Generator $generator, string $name): Generator
    {
        return (new GroupIntoStreamed($name))($generator, $this->splitter);
    }

    /**
     * @template Q
     * @param class-string<Q> $typeName
     * @return Collection<I,Q>
     */
    public function cast(string $typeName): Collection
    {
        $generator = (new Cast($typeName))($this->records, $this->splitter);
        return new Collection($generator, $this->streamingMode);
    }

    /**
     * @template Q
     * @param class-string<Q> $typeName
     * @param string $name
     * @return Selector<I,K|string,V|Q|null>
     */
    public function castInto(string $typeName, string $name): Selector
    {
        $generator = (new CastInto($typeName, $name))($this->records, $this->splitter);
        return new Selector($generator, $this->streamingMode);
    }
}
