<?php declare(strict_types=1);

namespace Hamlet\Database\Processing\Split;

use Generator;

class SelectByPrefix
{
    /**
     * @var string
     */
    private $prefix;

    /**
     * @var int
     */
    private $length;

    /**
     * @var array<string,array<string,string|false>>
     */
    private static $prefixCache = [];

    public function __construct(string $prefix)
    {
        $this->prefix = $prefix;
        $this->length = strlen($prefix);
    }

    /**
     * @template K as array-key
     * @template V
     * @param array<K,V> $record
     * @return array{array<string,V>,array<K,V>}
     */
    public function apply(array $record): array
    {
        $item = [];
        foreach ($record as $field => &$value) {
            if (!is_string($field)) {
                continue;
            }
            if (isset(self::$prefixCache[$field][$this->prefix])) {
                $suffix = self::$prefixCache[$field][$this->prefix];
            } else {
                if (strpos($field, $this->prefix) === 0) {
                    $suffix = substr($field, $this->length);
                } else {
                    $suffix = false;
                }
                self::$prefixCache[$field][$this->prefix] = $suffix;
            }
            if ($suffix) {
                $item[$suffix] = $value;
                unset($record[$field]);
            }
        }
        return [$item, $record];
    }

    /**
     * @template I as array-key
     * @template K as array-key
     * @template V
     * @param Generator<I,array<K,V>> $source
     * @return Generator<I,array{array<string,V>,array<K,V>}>
     */
    public function transform(Generator $source): Generator
    {
        foreach ($source as $key => $record) {
            yield $key => $this->apply($record);
        }
    }
}
