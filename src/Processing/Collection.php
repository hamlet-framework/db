<?php declare(strict_types=1);

namespace Hamlet\Database\Processing;

use Generator;
use Hamlet\Cast\Type;
use Iterator;

/**
 * @template I as array-key
 * @template T
 */
class Collection
{
    /**
     * @var Generator<I,T>
     */
    protected $source;

    /**
     * @var bool
     */
    protected $streamingMode;

    /**
     * @var Type|null
     */
    protected $keyType = null;

    /**
     * @var Type|null
     */
    protected $valueType = null;

    /**
     * @var (callable(I,T):bool)|null
     */
    protected $assertion = null;

    /**
     * @param Generator<I,T> $source
     * @param bool $streamingMode
     */
    public function __construct(Generator $source, bool $streamingMode)
    {
        $this->source = $source;
        $this->streamingMode = $streamingMode;
    }

    /**
     * @return array<I,T>
     */
    public function collectAll(): array
    {
        $result = [];
        foreach ($this->source as $key => $value) {
            $this->validate($key, $value);
            $result[$key] = $value;
        }
        return $result;
    }

    /**
     * @return T|null
     */
    public function collectHead()
    {
        foreach ($this->source as $key => $value) {
            $this->validate($key, $value);
            return $value;
        }
        return null;
    }

    /**
     * @return Iterator<I,T>
     */
    public function iterator(): Iterator
    {
        foreach ($this->source as $key => $value) {
            $this->validate($key, $value);
            yield $key => $value;
        }
    }

    /**
     * @template K as int|string
     * @template V
     * @param Type<K> $keyType
     * @param Type<V> $valueType
     * @return $this
     * @psalm-assert Collection<K,V> $this
     */
    public function assertType(Type $keyType, Type $valueType): self
    {
        $this->keyType = $keyType;
        $this->valueType = $valueType;
        return $this;
    }

    /**
     * @param callable(I,T):bool $callback
     * @return self<I,T>
     */
    public function assertForEach(callable $callback): self
    {
        $this->assertion = $callback;
        return $this;
    }

    /**
     * @param I $key
     * @param T $value
     * @return void
     */
    private function validate($key, $value)
    {
        assert(
            ($this->keyType === null || $this->keyType->matches($key)) &&
            ($this->valueType === null || $this->valueType->matches($value)) &&
            ($this->assertion === null || ($this->assertion)($key, $value))
        );
    }
}
