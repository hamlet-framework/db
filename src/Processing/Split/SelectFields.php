<?php declare(strict_types=1);

namespace Hamlet\Database\Processing\Split;

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
     * @return array{0:array<string,V>,1:array<K,V>}
     */
    public function __invoke(array $record): array
    {
        $item = [];
        foreach ($this->fields as $field) {
            if (!array_key_exists($field, $record)) {
                throw new DatabaseException('Property "' . $field . '" not set in ' . var_export($record, true));
            }
            $item[$field] = $record[$field];
            unset($record[$field]);
        }
        return [$item, $record];
    }
}
