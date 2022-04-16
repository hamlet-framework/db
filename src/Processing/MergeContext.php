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
 * @template I as array-key
 * @template K as array-key
 * @template V
 * @template E
 */
class MergeContext
{
    use EntityFactoryTrait;

    /**
     * @param Generator<I,array{E,array<K,V>}> $source
     * @param bool $streamingMode
     */
    public function __construct(protected readonly Generator $source, protected readonly bool $streamingMode)
    {
    }

    /**
     * @param string $name
     * @return SplitContext<I,K|string,V|E>
     */
    public function name(string $name): SplitContext
    {
        $generator = new Name($name);
        return new SplitContext($generator->transform($this->source), $this->streamingMode);
    }

    /**
     * @return Collection<I,list<E>>
     */
    public function group(): Collection
    {
        if ($this->streamingMode) {
            $generator = new GroupStreamed;
        } else {
            $generator = new GroupBatched;
        }
        return new Collection($generator->transform($this->source), $this->streamingMode);
    }

    /**
     * @param string $name
     * @return SplitContext<I,K|string,V|array<E>>
     */
    public function groupInto(string $name): SplitContext
    {
        if ($this->streamingMode) {
            $generator = new GroupIntoStreamed($name);
        } else {
            $generator = new GroupIntoBatched($name);
        }
        return new SplitContext($generator->transform($this->source), $this->streamingMode);
    }

    /**
     * @template Q as object
     * @param class-string<Q> $typeName
     * @return Collection<I,Q>
     */
    public function cast(string $typeName): Collection
    {
        $generator = new Cast($typeName);
        return new Collection($generator->transform($this->source), $this->streamingMode);
    }

    /**
     * @template Q as object
     * @param class-string<Q> $typeName
     * @param string $name
     * @return SplitContext<I,K|string,V|Q>
     */
    public function castInto(string $typeName, string $name): SplitContext
    {
        $generator = new CastInto($typeName, $name);
        return new SplitContext($generator->transform($this->source), $this->streamingMode);
    }
}
