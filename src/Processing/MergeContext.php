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
     * @var Generator<I,array{E,array<K,V>}>
     */
    protected $source;

    /**
     * @var bool
     */
    protected $streamingMode;

    /**
     * @param Generator<I,array{E,array<K,V>}> $source
     * @param bool $streamingMode
     */
    public function __construct(Generator $source, bool $streamingMode)
    {
        $this->source = $source;
        $this->streamingMode = $streamingMode;
    }

    /**
     * @param string $name
     * @return SplitContext<I,K|string,V|E>
     */
    public function name(string $name): SplitContext
    {
        $generator = (new Name($name))($this->source);
        return new SplitContext($generator, $this->streamingMode);
    }

    /**
     * @return Collection<I,array<I,E>|V>
     */
    public function group(): Collection
    {
        if ($this->streamingMode) {
            $generator = (new GroupStreamed)($this->source);
        } else {
            $generator = (new GroupBatched)($this->source);
        }
        return new Collection($generator, $this->streamingMode);
    }

    /**
     * @param string $name
     * @return SplitContext<I,K|string,V|array<E>>
     */
    public function groupInto(string $name): SplitContext
    {
        if ($this->streamingMode) {
            $generator = (new GroupIntoStreamed($name))($this->source);
        } else {
            $generator = (new GroupIntoBatched($name))($this->source);
        }
        return new SplitContext($generator, $this->streamingMode);
    }

    /**
     * @template Q
     * @param class-string<Q> $typeName
     * @return Collection<I,Q>
     */
    public function cast(string $typeName): Collection
    {
        $generator = (new Cast($typeName))($this->source);
        return new Collection($generator, $this->streamingMode);
    }

    /**
     * @template Q
     * @param class-string<Q> $typeName
     * @param string $name
     * @return SplitContext<I,K|string,V|Q>
     */
    public function castInto(string $typeName, string $name): SplitContext
    {
        $generator = (new CastInto($typeName, $name))($this->source);
        return new SplitContext($generator, $this->streamingMode);
    }
}
