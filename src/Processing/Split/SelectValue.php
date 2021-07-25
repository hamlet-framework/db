<?php declare(strict_types=1);

namespace Hamlet\Database\Processing\Split;

use Hamlet\Database\DatabaseException;

class SelectValue
{
    /**
     * @var string
     */
    private $field;

    public function __construct(string $field)
    {
        $this->field = $field;
    }

    /**
     * @template K as array-key
     * @template V
     * @param array<K,V> $record
     * @return array{0:V,1:array<K,V>}
     */
    public function __invoke(array $record): array
    {
        if (!array_key_exists($this->field, $record)) {
            throw new DatabaseException('Property "' . $this->field . '" not set in ' . var_export($record, true));
        }
        /**
         * @psalm-suppress PossiblyInvalidArrayOffset
         */
        $item = $record[$this->field];
        unset($record[$this->field]);
        return [$item, $record];
    }
}
