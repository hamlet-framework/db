<?php declare(strict_types=1);

namespace Hamlet\Database\Processing\Split;

class SelectAll
{
    /**
     * @template K as array-key
     * @template V
     * @param array<K,V> $record
     * @return array{0:array<K,V>,1:array<K,V>}
     */
    public function __invoke(array $record): array
    {
        return [$record, []];
    }
}
