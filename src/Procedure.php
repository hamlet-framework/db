<?php

namespace Hamlet\Database;

use Generator;
use Hamlet\Database\Processing\Selector;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use function assert;
use function is_float;
use function is_int;
use function is_string;
use function iterator_to_array;

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

    /**
     * @param LoggerInterface $logger
     * @return void
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param string $value
     * @return void
     */
    public function bindBlob(string $value)
    {
        $this->parameters[] = ['b', $value];
    }

    /**
     * @param float $value
     * @return void
     */
    public function bindFloat(float $value)
    {
        $this->parameters[] = ['d', $value];
    }

    /**
     * @param int $value
     * @return void
     */
    public function bindInteger(int $value)
    {
        $this->parameters[] = ['i', $value];
    }

    /**
     * @param string $value
     * @return void
     */
    public function bindString(string $value)
    {
        $this->parameters[] = ['s', $value];
    }

    /**
     * @param string|null $value
     * @return void
     */
    public function bindNullableBlob($value)
    {
        $this->parameters[] = ['b', $value];
    }

    /**
     * @param float|null $value
     * @return void
     */
    public function bindNullableFloat($value)
    {
        $this->parameters[] = ['d', $value];
    }

    /**
     * @param int|null $value
     * @return void
     */
    public function bindNullableInteger($value)
    {
        $this->parameters[] = ['i', $value];
    }

    /**
     * @param string|null $value
     * @return void
     */
    public function bindNullableString(?string $value)
    {
        $this->parameters[] = ['s', $value];
    }

    /**
     * @param array<float> $values
     * @return void
     */
    public function bindFloatList(array $values)
    {
        assert(!empty($values));
        foreach ($values as $value) {
            assert(is_float($value));
        }
        $this->parameters[] = ['d', $values];
    }

    /**
     * @param array<int> $values
     * @return void
     */
    public function bindIntegerList(array $values)
    {
        assert(!empty($values));
        foreach ($values as $value) {
            assert(is_int($value));
        }
        $this->parameters[] = ['i', $values];
    }

    /**
     * @param array<string> $values
     * @return void
     */
    public function bindStringList(array $values)
    {
        assert(!empty($values));
        foreach ($values as $value) {
            assert(is_string($value));
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

    /**
     * @return void
     */
    abstract public function execute();

    abstract public function affectedRows(): int;
}
