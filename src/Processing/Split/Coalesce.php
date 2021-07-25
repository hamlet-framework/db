<?php declare(strict_types=1);

namespace Hamlet\Database\Processing\Split;

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
     * @return array{0:V|null,1:array<K,V>}
     */
    public function __invoke(array $record): array
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
}
