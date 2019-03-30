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
     * @var array
     * @psalm-var array<array{0:string,1:string|float|int|array<string>|array<float>|array<int>|null}>
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
        $this->parameters[] = ['b', $value];
    }

    public function bindNullableFloat(?float $value): void
    {
        $this->parameters[] = ['d', $value];
    }

    public function bindNullableInteger(?int $value): void
    {
        $this->parameters[] = ['i', $value];
    }

    public function bindNullableString(?string $value): void
    {
        $this->parameters[] = ['s', $value];
    }

    /**
     * @param array<float> $values
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
     * @param array<int> $values
     */
    public function bindIntegerList(array $values): void
    {
        \assert(!empty($values));
        foreach ($values as $value) {
            \assert(\is_int($value));
        }
        $this->parameters[] = ['i', $values];
    }

    /**
     * @param array<string> $values
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
     * @return Generator
     * @psalm-return Generator<int,array<string,int|string|float|null>,mixed,void>
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
     * @return Selector
     * @psalm-return Selector<int,string,int|string|float|null>
     */
    public function processOne(): Selector
    {
        $generator =
            /**
             * @return Generator
             * @psalm-return Generator<int,array<string,int|string|float|null>,mixed,void>
             */
            function () {
                foreach ($this->fetch() as $key => $record) {
                    yield $key => $record;
                    return;
                }
            };
        return new Selector($generator(), false);
    }

    /**
     * @return Selector
     * @psalm-return Selector<int,string,int|string|float|null>
     */
    public function processAll(): Selector
    {
        return new Selector($this->fetch(), false);
    }

    /**
     * @return Selector
     * @psalm-return Selector<int,string,int|string|float|null>
     */
    public function stream(): Selector
    {
        return new Selector($this->fetch(), true);
    }

    abstract public function insert(): int;

    abstract public function execute(): void;

    abstract public function affectedRows(): int;
}
