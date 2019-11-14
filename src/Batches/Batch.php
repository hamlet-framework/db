<?php

namespace Hamlet\Database\Batches;

use Hamlet\Database\Procedure;
use Hamlet\Database\Processing\Collector;

/**
 * @template T
 */
abstract class Batch
{
    /**
     * @var array
     * @psalm-var array<callable(\Hamlet\Database\Session):T>
     */
    protected $items = [];

    protected function __construct()
    {
    }

    public static function collectingHeads(): self
    {
        return new BatchCollectingHeads;
    }

    public static function collectingAll(): self
    {
        return new BatchCollectingAll;
    }

    public static function executing(): self
    {
        return new BatchExecuting;
    }

    /**
     * @param mixed $item
     * @psalm-param callable(\Hamlet\Database\Session):T $item
     * @return self
     */
    public function push($item): self
    {
        $this->items[] = $item;
        return $this;
    }

    /**
     * @return array
     * @psalm-return array<callable(\Hamlet\Database\Session):T>
     */
    public function items(): array
    {
        return $this->items;
    }

    /**
     * @param Procedure|Collector $input
     * @psalm-param T $input
     * @return mixed
     */
    abstract public function apply($input);
}
