<?php declare(strict_types=1);

namespace Hamlet\Database\Processing\Split;

use Hamlet\Database\DatabaseException;

class Map
{
    /**
     * @var string
     */
    private $keyField;

    /**
     * @var string
     */
    private $valueField;

    public function __construct(string $keyField, string $valueField)
    {
        $this->keyField = $keyField;
        $this->valueField = $valueField;
    }

    /**
     * @template K as array-key
     * @template V
     * @param array<K,V|null> $record
     * @return array{0:array<V>,1:array<K,V|null>}
     */
    public function __invoke(array $record): array
    {
        if (!array_key_exists($this->keyField, $record)) {
            throw new DatabaseException('Property "' . $this->keyField . '" not set in ' . var_export($record, true));
        }
        /**
         * @psalm-suppress PossiblyInvalidArrayOffset
         */
        $key = $record[$this->keyField];
        if (!(is_null($key) || is_scalar($key) || (is_object($key) && method_exists($key, '__toString')))) {
            throw new DatabaseException('Cannot use property "' . $this->keyField . '" as a key in ' . var_export($record, true));
        }
        if (!is_int($key)) {
            $key = (string) $key;
        }
        if (!array_key_exists($this->valueField, $record)) {
            throw new DatabaseException('Property "' . $this->valueField . '" not set in ' . var_export($record, true));
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
}