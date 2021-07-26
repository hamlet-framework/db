<?php declare(strict_types=1);

namespace Hamlet\Database\Processing\Split;

use Generator;
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
     * @return array{V,array<K,V>}
     */
    public function apply(array $record): array
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

    /**
     * @template I as array-key
     * @template K as array-key
     * @template V
     * @param Generator<I,array<K,V>> $source
     * @return Generator<I,array{V,array<K,V>}>
     */
    public function transform(Generator $source): Generator
    {
        foreach ($source as $key => $record) {
            yield $key => $this->apply($record);
        }
    }
}
