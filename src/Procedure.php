<?php

namespace Hamlet\Database;

use Generator;
use Hamlet\Database\Processing\Selector;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

abstract class Procedure implements LoggerAwareInterface
{
    /**
     * @var LoggerInterface|null
     */
    protected $logger;

    /**
     * @var array<array{0:string,1:string|float|int|array<string>|array<float>|array<int>|null}>
     */
    protected $parameters = [];

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function bindBlob(string $value): void
    {
        $this->parameters[] = ['b', $value];
    }

    public function bindFloat(float $value): void
    {
        $this->parameters[] = ['d', $value];
    }

    public function bindInteger(int $value): void
    {
        $this->parameters[] = ['i', $value];
    }

    public function bindString(string $value): void
    {
        $this->parameters[] = ['s', $value];
    }

    public function bindNullableBlob(?string $value): void
    {
        assert($value === null || \is_string($value));
        $this->parameters[] = ['b', $value];
    }

    public function bindNullableFloat(?float $value): void
    {
        \assert($value === null || \is_float($value));
        $this->parameters[] = ['d', $value];
    }

    public function bindNullableInteger(?int $value): void
    {
        \assert($value === null || \is_int($value));
        $this->parameters[] = ['i', $value];
    }

    public function bindNullableString(?string $value): void
    {
        \assert($value === null || \is_string($value));
        $this->parameters[] = ['s', $value];
    }

    /**
     * @param float[] $values
     */
    public function bindFloatList(array $values): void
    {
        \assert(!empty($values));
        foreach ($values as $value) {
            \assert(\is_float($value));
        }
        $this->parameters[] = ['d', $values];
    }

    /**
     * @param int[] $values
     */
    public function bindIntegerList(array $values): void
    {
        \assert(!empty($values));
        foreach ($values as $value) {
            assert(\is_int($value));
        }
        $this->parameters[] = ['i', $values];
    }

    /**
     * @param string[] $values
     */
    public function bindStringList(array $values): void
    {
        \assert(!empty($values));
        foreach ($values as $value) {
            \assert(\is_string($value));
        }
        $this->parameters[] = ['s', $values];
    }

    /**
     * @return Generator<int,array<string,int|string|float|null>>
     */
    abstract protected function fetch(): Generator;

    /**
     * @return array<string,int|string|float|null>|null
     */
    public function fetchOne(): ?array
    {
        foreach ($this->fetch() as $record) {
            return $record;
        }
        return null;
    }

    /**
     * @return array<int,array<string,int|string|float|null>>
     */
    public function fetchAll(): array
    {
        return iterator_to_array($this->fetch());
    }

    /**
     * @return Selector<int,array<string,int|string|float|null>>
     */
    public function processOne(): Selector
    {
        $generator = function () {
            foreach ($this->fetch() as $key => $record) {
                yield $key => $record;
                return;
            }
        };
        return new Selector($generator(), false);
    }

    /**
     * @return Selector<int,array<string,int|string|float|null>>
     */
    public function processAll(): Selector
    {
        return new Selector($this->fetch(), false);
    }

    /**
     * @return Selector<int,array<string,int|string|float|null>>
     */
    public function stream(): Selector
    {
        return new Selector($this->fetch(), true);
    }

    abstract public function insert(): int;

    abstract public function execute(): void;

    abstract public function affectedRows(): int;
}
