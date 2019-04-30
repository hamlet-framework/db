<?php

namespace Hamlet\Database\Traits;

use function assert;
use function is_float;
use function is_int;
use function is_null;
use function is_string;
use function strlen;
use function strpos;
use function substr;

/**
 * @template K as array-key
 * @template V
 */
trait SplitterTrait
{
    /**
     * @var array
     * @psalm-var array<string,array<string,string>>
     */
    private static $prefixCache = [];

    /**
     * @param string $field
     * @return callable
     * @psalm-return callable(array<K,V>):array{0:V,1:array<K,V>}
     */
    private function selectValueSplitter(string $field): callable
    {
        return
            /**
             * @param array $record
             * @psalm-param array<K,V> $record
             * @return array
             * @psalm-return array{0:V,1:array<K,V>}
             */
            function (array $record) use ($field): array {
                $item = $record[$field];
                unset($record[$field]);
                return [$item, $record];
            };
    }

    /**
     * @param string $field
     * @param string ...$fields
     * @return callable
     * @psalm-return callable(array<K,V>):array{0:array<K,V>,1:array<K,V>}
     */
    private function selectFieldsSplitter(string $field, string ...$fields): callable
    {
        array_unshift($fields, $field);
        return
            /**
             * @param array $record
             * @psalm-param array<K,V> $record
             * @return array
             * @psalm-return array{0:array<K,V>,1:array<K,V>}
             */
            function (array $record) use ($fields): array {
                $item = [];
                foreach ($fields as &$field) {
                    $item[$field] = $record[$field];
                    unset($record[$field]);
                }
                return [$item, $record];
            };
    }

    /**
     * @param string $keyField
     * @param string $valueField
     * @return callable
     * @psalm-return callable(array<K,V>):array{0:array<int|string|float|null,V>,1:array<K, V>}
     */
    private function mapSplitter(string $keyField, string $valueField): callable
    {
        return
            /**
             * @param array $record
             * @psalm-param array<K,V> $record
             * @return array
             * @psalm-return array{0:array<int|string|float|null,V>,1:array<K,V>}
             */
            function (array $record) use ($keyField, $valueField): array {
                $key = $record[$keyField];
                assert(is_null($key) || is_int($key) || is_string($key) || is_float($key));
                $item = [
                    $key => $record[$valueField]
                ];
                unset($record[$keyField]);
                unset($record[$valueField]);
                return [$item, $record];
            };
    }

    /**
     * @param string $prefix
     * @return callable
     * @psalm-return callable(array<K,V>):array{0:array<string,V>,1:array<K,V>}
     */
    private function selectByPrefixSplitter(string $prefix): callable
    {
        $length = strlen($prefix);
        return
            /**
             * @param array $record
             * @psalm-param array<K,V> $record
             * @return array
             * @psalm-return array{0:array<string,V>,1:array<K,V>}
             */
            function (array $record) use ($prefix, $length): array {
                $item = [];
                foreach ($record as $field => &$value) {
                    $suffix = false;
                    if (is_string($field)) {
                        if (isset(self::$prefixCache[$field][$prefix])) {
                            $suffix = self::$prefixCache[$field][$prefix];
                        } else {
                            if (strpos($field, $prefix) === 0) {
                                $suffix = substr($field, $length);
                            }
                            self::$prefixCache[$field][$prefix] = $suffix;
                        }
                    }
                    if ($suffix) {
                        $item[$suffix] = $value;
                        unset($record[$field]);
                    }
                }
                return [$item, $record];
            };
    }

    /**
     * @return callable
     * @psalm-return callable(array<K,V>):array{0:array<K,V>,1:array<K,V>}
     */
    private function selectAllSplitter(): callable
    {
        return
            /**
             * @param array $record
             * @psalm-param array<K,V> $record
             * @return array
             * @psalm-return array{0:array<K,V>,1:array<K,V>}
             */
            function (array $record): array {
                return [$record, []];
            };
    }

    /**
     * @param string $field
     * @param string ...$fields
     * @return callable
     * @psalm-return callable(array<K,V>):array{0:V|null,1:array<K,V>}
     */
    private function coalesceSplitter(string $field, string ...$fields): callable
    {
        array_unshift($fields, $field);
        return
            /**
             * @param array $record
             * @psalm-param array<K,V> $record
             * @return array
             * @psalm-return array{0:V|null,1:array<K,V>}
             */
            function (array $record) use ($fields): array {
                $item = null;
                foreach ($fields as &$field) {
                    if ($item === null) {
                        $item = $record[$field];
                    }
                    unset($record[$field]);
                }
                return [$item, $record];
            };
    }
}
