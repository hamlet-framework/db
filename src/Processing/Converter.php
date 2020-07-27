<?php declare(strict_types=1);

namespace Hamlet\Database\Processing;

use Generator;
use Hamlet\Cast\ClassType;
use Hamlet\Database\Resolvers\EntityResolver;
use Hamlet\Database\Traits\EntityFactoryTrait;
use RuntimeException;
use function assert;
use function is_null;
use function md5;
use function serialize;

/**
 * I - Index type of all records
 * K - Key type of original record
 * V - Value type of original record
 * E - Type of element extracted by splitter
 *
 * @template I as array-key
 * @template K as array-key
 * @template V
 * @template E
 */
class Converter
{
    use EntityFactoryTrait;

    /**
     * @var Generator<I,array<K,V>,mixed,void>
     */
    protected $records;

    /**
     * @var callable(array<K,V>):array{0:E,1:array<K,V>}
     */
    protected $splitter;

    /**
     * @var bool
     */
    protected $streamingMode;

    /**
     * @param Generator<I,array<K,V>,mixed,void> $records
     * @param callable(array<K,V>):array{0:E,1:array<K,V>} $splitter
     * @param bool $streamingMode
     */
    public function __construct(Generator $records, callable $splitter, bool $streamingMode)
    {
        $this->records = $records;
        $this->splitter = $splitter;
        $this->streamingMode = $streamingMode;
    }

    /**
     * @param string $name
     * @return Selector<I,K|string,V|E>
     */
    public function name(string $name): Selector
    {
        $generator =
            /**
             * @return Generator<I,array<K|string,V|E>,mixed,void>
             */
            function () use ($name) {
                foreach ($this->records as $key => $record) {
                    list($item, $record) = ($this->splitter)($record);
                    $record[$name] = $item;
                    yield $key => $record;
                }
            };
        return new Selector($generator(), $this->streamingMode);
    }

    /**
     * @return Collector<I,array<I,E>|V>
     */
    public function group(): Collector
    {
        $generator =
            /**
             * @return Generator<I,array<I,E>|V,mixed,void>
             */
            function () {
                foreach ($this->groupRecordsInto(':property:') as $key => $record) {
                    yield $key => $record[':property:'];
                }
            };
        return new Collector($generator(), $this->streamingMode);
    }

    /**
     * @param string $name
     * @return Selector<I,K|string,V|array<E>>
     */
    public function groupInto(string $name): Selector
    {
        return new Selector($this->groupRecordsInto($name), $this->streamingMode);
    }

    /**
     * @param string $name
     * @return Generator<I,array<K|string,V|array<E>>,mixed,void>
     */
    private function groupRecordsInto(string $name): Generator
    {
        if ($this->streamingMode) {
            return $this->groupRecordsStreamingMode($this->records, $name);
        } else {
            return $this->groupRecordsBatchMode($this->records, $name);
        }
    }

    /**
     * @param Generator<I,array<K,V>,mixed,void> $generator
     * @param string $name
     * @return Generator<I,array<K|string,V|list<E>>,mixed,void>
     */
    private function groupRecordsBatchMode(Generator $generator, string $name): Generator
    {
        /** @var array<I,array<K,V>> $records */
        $records = [];

        /** @var array<I,list<E>> $groups */
        $groups = [];

        /** @var array<string,I> $keys */
        $keys = [];

        foreach ($generator as $key => $record) {
            list($item, $record) = ($this->splitter)($record);
            $hash = md5(serialize($record));
            if (!isset($keys[$hash])) {
                $keys[$hash] = $key;
            } else {
                $key = $keys[$hash];
            }
            if (!isset($groups[$key])) {
                $groups[$key] = [];
            }
            if (!$this->isNull($item)) {
                $groups[$key][] = $item;
            }
            $records[$key] = $record;
        }

        foreach ($records as $key => &$record) {
            $record[$name] = $groups[$key];
            yield $key => $record;
        }
    }

    /**
     * @param Generator<I,array<K,V>,mixed,void> $generator
     * @param string $name
     * @return Generator<I,array<K|string,V|list<E>>,mixed,void>
     */
    private function groupRecordsStreamingMode(Generator $generator, string $name): Generator
    {
        $currentGroup = null;
        $lastRecord = null;
        $lastKey = null;
        foreach ($generator as $key => $record) {
            list($item, $record) = ($this->splitter)($record);
            if ($lastRecord !== $record) {
                if (!$this->isNull($currentGroup)) {
                    if ($lastRecord === null) {
                        $lastRecord = [];
                    }
                    $lastRecord[$name] = $currentGroup;
                    if (!$this->isNull($lastRecord)) {
                        assert(!is_null($lastKey) && !is_null($lastRecord));
                        yield $lastKey => $lastRecord;
                    }
                }
                $lastKey = $key;
                $currentGroup = [];
            }
            if (!$this->isNull($item)) {
                $currentGroup[] = $item;
            }
            $lastRecord = $record;
        }
        $lastRecord[$name] = $currentGroup;
        if (!$this->isNull($lastRecord)) {
            assert(!is_null($lastKey) && !is_null($lastRecord));
            yield $lastKey => $lastRecord;
        }
    }

    /**
     * @template Q
     * @param class-string<Q> $typeName
     * @return Collector<I,Q>
     */
    public function cast(string $typeName): Collector
    {
        $generator =
            /**
             * @return Generator<I,Q,mixed,void>
             * @psalm-suppress InvalidReturnType
             */
            function () use ($typeName) {
                foreach ($this->castRecordsInto($typeName, ':property:') as $key => $record) {
                    yield $key => $record[':property:'];
                }
            };
        return new Collector($generator(), $this->streamingMode);
    }

    /**
     * @template Q
     * @param class-string<Q> $typeName
     * @param string $name
     * @return Selector<I,K|string,V|Q|null>
     */
    public function castInto(string $typeName, string $name): Selector
    {
        return new Selector($this->castRecordsInto($typeName, $name), $this->streamingMode);
    }

    /**
     * @template Q
     * @param class-string<Q> $typeName
     * @param string $name
     * @return Generator<I,array<K|string,V|Q|null>,mixed,void>
     */
    private function castRecordsInto(string $typeName, string $name): Generator
    {
        $type = new ClassType($typeName);
        $entityResolver = new EntityResolver;
        foreach ($this->records as $key => $record) {
            list($item, $record) = ($this->splitter)($record);
            $instance = $type->resolveAndCast($item, $entityResolver);
            if (!($instance instanceof $typeName)) {
                throw new RuntimeException('Cannot ' . print_r($item, true) .  ' to ' . $typeName);
            }
            $record[$name] = $instance;
            yield $key => $record;
        }
    }
}
