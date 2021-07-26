<?php declare(strict_types=1);

namespace Hamlet\Database;

use Generator;
use Hamlet\Database\Processing\SplitContext;
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
     * @var array<array{0:string,1:string|float|int|array<string>|array<float>|array<int>|null}>
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
     * @return static
     */
    public function bindBlob(string $value)
    {
        $this->parameters[] = ['b', $value];
        return $this;
    }

    /**
     * @param float $value
     * @return static
     */
    public function bindFloat(float $value)
    {
        $this->parameters[] = ['d', $value];
        return $this;
    }

    /**
     * @param int $value
     * @return static
     */
    public function bindInteger(int $value)
    {
        $this->parameters[] = ['i', $value];
        return $this;
    }

    /**
     * @param string $value
     * @return static
     */
    public function bindString(string $value)
    {
        $this->parameters[] = ['s', $value];
        return $this;
    }

    /**
     * @param string|null $value
     * @return static
     */
    public function bindNullableBlob($value)
    {
        $this->parameters[] = ['b', $value];
        return $this;
    }

    /**
     * @param float|null $value
     * @return static
     */
    public function bindNullableFloat($value)
    {
        $this->parameters[] = ['d', $value];
        return $this;
    }

    /**
     * @param int|null $value
     * @return static
     */
    public function bindNullableInteger($value)
    {
        $this->parameters[] = ['i', $value];
        return $this;
    }

    /**
     * @param string|null $value
     * @return static
     */
    public function bindNullableString($value)
    {
        $this->parameters[] = ['s', $value];
        return $this;
    }

    /**
     * @param array<float> $values
     * @return static
     */
    public function bindFloatList(array $values)
    {
        assert(!empty($values));
        foreach ($values as $value) {
            assert(is_float($value));
        }
        $this->parameters[] = ['d', $values];
        return $this;
    }

    /**
     * @param array<int> $values
     * @return static
     */
    public function bindIntegerList(array $values)
    {
        assert(!empty($values));
        foreach ($values as $value) {
            assert(is_int($value));
        }
        $this->parameters[] = ['i', $values];
        return $this;
    }

    /**
     * @param array<string> $values
     * @return static
     */
    public function bindStringList(array $values)
    {
        assert(!empty($values));
        foreach ($values as $value) {
            assert(is_string($value));
        }
        $this->parameters[] = ['s', $values];
        return $this;
    }

    /**
     * @return Generator<int,array<string,int|string|float|null>>
     */
    abstract protected function fetch(): Generator;

    /**
     * @return array<string,int|string|float|null>|null
     */
    public function fetchOne()
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
     * @return SplitContext<int,string,int|string|float|null>
     */
    public function processOne(): SplitContext
    {
        $generator =
            /**
             * @return Generator<int,array<string,int|string|float|null>>
             */
            function () {
                foreach ($this->fetch() as $key => $record) {
                    yield $key => $record;
                    return;
                }
            };
        return new SplitContext($generator(), false);
    }

    /**
     * @return SplitContext<int,string,int|string|float|null>
     */
    public function processAll(): SplitContext
    {
        return new SplitContext($this->fetch(), false);
    }

    /**
     * @return SplitContext<int,string,int|string|float|null>
     */
    public function stream(): SplitContext
    {
        return new SplitContext($this->fetch(), true);
    }

    abstract public function insert(): int;

    /**
     * @return void
     */
    abstract public function execute();

    abstract public function affectedRows(): int;
}
