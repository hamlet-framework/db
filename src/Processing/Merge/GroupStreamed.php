<?php declare(strict_types=1);

namespace Hamlet\Database\Processing\Merge;

use Generator;
use Hamlet\Database\Traits\EntityFactoryTrait;

class GroupStreamed extends GroupIntoStreamed
{
    use EntityFactoryTrait;

    public function __construct()
    {
        parent::__construct(':property:');
    }

    /**
     * @template I as array-key
     * @template K as array-key
     * @template V
     * @template E
     * @param Generator<I,array{E,array<K,V>}> $records
     * @return Generator<I,list<E>>
     * @psalm-suppress ImplementedReturnTypeMismatch
     * @psalm-suppress MixedReturnTypeCoercion
     */
    public function transform(Generator $records): Generator
    {
        foreach (parent::transform($records) as $key => $record) {
            assert(array_key_exists(':property:', $record));
            $value = $record[':property:'];
            yield $key => $value;
        }
    }
}
