<?php

namespace Hamlet\Database\Processing;

use Generator;
use Hamlet\Cast\Type;
use Iterator;

/**
 * @template I as array-key
 * @template T
 */
class Collector
{
    /**
     * @var Generator
     * @psalm-var Generator<I,T,mixed,void>
     */
    protected $records;

    /**
     * @var bool
     */
    protected $streamingMode;

    /**
     * @var Type|null
     */
    protected $keyType;

    /**
     * @var Type|null
     */
    protected $valueType;

    /**
     * @var callable|null
     * @psalm-var (callable(mixed,mixed):bool)|null
     */
    protected $assertion;

    /**
     * @param Generator $records
     * @psalm-param Generator<I,T,mixed,void> $records
     * @param bool $streamingMode
     */
    public function __construct(Generator $records, bool $streamingMode)
    {
        $this->records = $records;
        $this->streamingMode = $streamingMode;
    }

    /**
     * @return array
     * @psalm-return array<I,T>
     */
    public function collectAll(): array
    {
        $result = [];
        foreach ($this->records as $key => $value) {
            $this->validate($key, $value);
            $result[$key] = $value;
        }
        return $result;
    }

    /**
     * @return mixed
     * @psalm-return T|null
     */
    public function collectHead()
    {
        foreach ($this->records as $key => $value) {
            $this->validate($key, $value);
            return $value;
        }
        return null;
    }

    /**
     * @return Iterator
     * @psalm-return Iterator<I,T>
     */
    public function iterator(): Iterator
    {
        foreach ($this->records as $key => $value) {
            $this->validate($key, $value);
            yield $key => $value;
        }
    }

    /**
     * @template K as array-key
     * @template V
     * @param Type $keyType
     * @psalm-param Type<K> $keyType
     * @param Type $valueType
     * @psalm-param Type<V> $valueType
     * @return Collector
     * @psalm-return Collector<K,V>
     * @psalm-suppress MixedTypeCoercion
     */
    public function assertType(Type $keyType, Type $valueType)
    {
        $this->keyType = $keyType;
        $this->valueType = $valueType;
        return $this;
    }

    /**
     * @param callable $callback
     * @psalm-param callable(mixed,mixed):bool $callback
     * @return self
     */
    public function assertForEach(callable $callback): self
    {
        $this->assertion = $callback;
        return $this;
    }

    /**
     * @param mixed $key
     * @psalm-param I $key
     * @param mixed $value
     * @psalm-param T $value
     * @return void
     */
    private function validate($key, $value): void
    {
        if ($this->keyType) {
            $this->keyType->assert($key);
        }
        if ($this->valueType) {
            $this->valueType->assert($value);
        }
        if ($this->assertion) {
            assert(($this->assertion)($key, $value));
        }
    }
}
