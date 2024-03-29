<?php declare(strict_types=1);

namespace Hamlet\Database\Processing\Split;

use Generator;
use Hamlet\Database\DatabaseException;

class Map
{
    public function __construct(private readonly string $keyField, private readonly string $valueField)
    {
    }

    /**
     * @template K as array-key
     * @template V
     * @param array<K,V> $record
     * @return array{array<V>,array<K,V>}
     */
    public function apply(array $record): array
    {
        if (!array_key_exists($this->keyField, $record)) {
            throw new DatabaseException(sprintf('Property "%s" not set in %s', $this->keyField, var_export($record, true)));
        }
        /**
         * @psalm-suppress PossiblyInvalidArrayOffset
         */
        $key = $record[$this->keyField];
        if (!is_null($key) && !is_scalar($key) && (!is_object($key) || !method_exists($key, '__toString'))) {
            throw new DatabaseException(sprintf('Cannot use property "%s" as a key in %s', $this->keyField, var_export($record, true)));
        }
        /**
         * @psalm-suppress DocblockTypeContradiction
         */
        if (!is_int($key)) {
            $key = (string) $key;
        }
        if (!array_key_exists($this->valueField, $record)) {
            throw new DatabaseException(sprintf('Property "%s" not set in %s', $this->valueField, var_export($record, true)));
        }
        /**
         * @psalm-suppress PossiblyInvalidArrayOffset
         */
        $item = [
            $key => $record[$this->valueField]
        ];
        unset($record[$this->keyField]);
        unset($record[$this->valueField]);
        return [$item, $record];
    }

    /**
     * @template I as array-key
     * @template K as array-key
     * @template V
     * @param Generator<I,array<K,V>> $source
     * @return Generator<I,array{array<V>,array<K,V>}>
     */
    public function transform(Generator $source): Generator
    {
        foreach ($source as $key => $record) {
            yield $key => $this->apply($record);
        }
    }
}
