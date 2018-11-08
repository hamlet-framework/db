<?php

namespace Hamlet\Database\Processing;

use Generator;
use Iterator;

/**
 * @template I
 * @template T
 */
class Collector
{
    /**
     * @var Generator<I,T,mixed,void>
     */
    protected $records;

    /**
     * @var bool
     */
    protected $streamingMode;

    /**
     * @param Generator<I,T,mixed,void> $records
     * @param bool $streamingMode
     */
    public function __construct(Generator $records, bool $streamingMode)
    {
        $this->records = $records;
        $this->streamingMode = $streamingMode;
    }

    /**
     * @return array<I,T>
     */
    public function collectAll(): array
    {
        return iterator_to_array($this->records);
    }

    /**
     * @return T|null
     */
    public function collectHead()
    {
        return $this->records->current();
    }

    /**
     * @return Iterator<I,T>
     */
    public function iterator(): Iterator
    {
        return $this->records;
    }
}
