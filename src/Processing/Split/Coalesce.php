<?php declare(strict_types=1);

namespace Hamlet\Database\Processing\Split;

use Generator;
use Hamlet\Database\DatabaseException;

class Coalesce
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
     * @return array{V|null,array<K,V>}
     */
    public function apply(array $record): array
    {
        $item = null;
        foreach ($this->fields as $field) {
            if ($item === null) {
                if (!array_key_exists($field, $record)) {
                    throw new DatabaseException('Property "' . $field . '" not set in ' . var_export($record, true));
                }
                $item = $record[$field];
            }
            unset($record[$field]);
        }
        return [$item, $record];
    }

    /**
     * @template I as array-key
     * @template K as array-key
     * @template V
     * @param Generator<I,array<K,V>> $source
     * @return Generator<I,array{V|null,array<K,V>}>
     */
    public function transform(Generator $source): Generator
    {
        foreach ($source as $key => $record) {
            yield $key => $this->apply($record);
        }
    }
}
