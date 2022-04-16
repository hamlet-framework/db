<?php declare(strict_types=1);

namespace Hamlet\Database;

use Generator;
use Hamlet\Database\Processing\SplitContext;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use function assert;
use function Hamlet\Cast\_array;
use function Hamlet\Cast\_float;
use function Hamlet\Cast\_int;
use function Hamlet\Cast\_string;
use function iterator_to_array;

abstract class Procedure implements LoggerAwareInterface
{
    protected ?LoggerInterface $logger;

    /**
     * @var array<array{0:string,1:string|float|int|array<string>|array<float>|array<int>|null}>
     */
    protected array $parameters = [];

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function bindBlob(string $value): static
    {
        $this->parameters[] = ['b', $value];
        return $this;
    }

    public function bindFloat(float $value): static
    {
        $this->parameters[] = ['d', $value];
        return $this;
    }

    public function bindInteger(int $value): static
    {
        $this->parameters[] = ['i', $value];
        return $this;
    }

    public function bindString(string $value): static
    {
        $this->parameters[] = ['s', $value];
        return $this;
    }

    public function bindNullableBlob(?string $value): static
    {
        $this->parameters[] = ['b', $value];
        return $this;
    }

    public function bindNullableFloat(?float $value): static
    {
        $this->parameters[] = ['d', $value];
        return $this;
    }

    public function bindNullableInteger(?int $value): static
    {
        $this->parameters[] = ['i', $value];
        return $this;
    }

    public function bindNullableString(?string $value): static
    {
        $this->parameters[] = ['s', $value];
        return $this;
    }

    /**
     * @param array<float> $values
     */
    public function bindFloatList(array $values): static
    {
        assert(_array(_float())->matches($values));
        $this->parameters[] = ['d', $values];
        return $this;
    }

    /**
     * @param array<int> $values
     */
    public function bindIntegerList(array $values): static
    {
        assert(_array(_int())->matches($values));
        $this->parameters[] = ['i', $values];
        return $this;
    }

    /**
     * @param array<string> $values
     */
    public function bindStringList(array $values): static
    {
        assert(_array(_string())->matches($values));
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

    abstract public function execute(): void;

    abstract public function affectedRows(): int;
}
