<?php

namespace Hamlet\Database\Processing;

use Hamlet\Database\Procedure;

/**
 * @template T
 */
class BatchProcessor
{
    /**
     * @var callable
     * @psalm-param callable(Procedure):T
     */
    private $processor;

    /**
     * @var callable[]
     * @psalm-var array<callable(Session):Procedure>
     */
    private $procedures = [];

    /**
     * @param callable $processor
     * @psalm-param callable(Procedure):T $processor
     */
    public function __construct($processor)
    {
        $this->processor = $processor;
    }

    /**
     * @param callable $procedure
     * @psalm-param callable(Session):Procedure $procedure
     * @return self
     */
    public function push(callable $procedure): self
    {
        $this->procedures[] = $procedure;
        return $this;
    }

    /**
     * @return callable[]
     * @psalm-return array<callable(Session):Procedure>
     */
    public function procedures(): array
    {
        return $this->procedures;
    }

    /**
     * @param Procedure $procedure
     * @return mixed
     * @psalm-return T
     */
    public function apply(Procedure $procedure)
    {
        return ($this->processor)($procedure);
    }
}
