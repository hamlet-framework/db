<?php declare(strict_types=1);

namespace Hamlet\Database\Processing\Split;

use Generator;
use Hamlet\Database\DatabaseException;

class SelectFields
{
    /**
     * @var array<string>
     */
    private $fields;

    /**
     * @param array<string> $fields
     */
    public function __construct(array $fields)
    {
        $this->fields = $fields;
    }

    /**
     * @template K as array-key
     * @template V
     * @param array<K,V> $record
     * @return array{array<K,V>,array<K,V>}
     * @psalm-suppress InvalidReturnStatement
     * @psalm-suppress InvalidReturnType
     */
    public function apply(array $record): array
    {
        $item = [];
        foreach ($this->fields as $field) {
            if (!array_key_exists($field, $record)) {
                throw new DatabaseException(sprintf('Property "%s" not set in %s', $field, var_export($record, true)));
            }
            $item[$field] = $record[$field];
            unset($record[$field]);
        }
        return [$item, $record];
    }

    /**
     * @template I as array-key
     * @template K as array-key
     * @template V
     * @param Generator<I,array<K,V>> $source
     * @return Generator<I,array{array<K,V>,array<K,V>}>
     */
    public function transform(Generator $source): Generator
    {
        foreach ($source as $key => $record) {
            yield $key => $this->apply($record);
        }
    }
}
