<?php

namespace Hamlet\Database\Traits;

use Hamlet\Database\DatabaseException;
use function is_int;
use function is_string;
use function strlen;
use function strpos;
use function substr;

trait SplitterTrait
{
    /**
     * @var array<array<string,string|false>>
     */
    private static $prefixCache = [];

    /**
     * @template K as array-key
     * @template V
     * @param string $field
     * @return callable(array<K,V>):array{0:V,1:array<K,V>}
     */
    private function selectValueSplitter(string $field): callable
    {
        return
            /**
             * @param array<K,V> $record
             * @return array{0:V,1:array<K,V>}
             */
            function (array $record) use ($field): array {
                if (!array_key_exists($field, $record)) {
                    throw new DatabaseException('Property "' . $field . '" not set in ' . var_export($record, true));
                }
                $item = $record[$field];
                unset($record[$field]);
                return [$item, $record];
            };
    }

    /**
     * @template K as array-key
     * @template V
     * @param string $field
     * @param string ...$fields
     * @return callable(array<K,V>):array{0:array<string,V>,1:array<K,V>}
     */
    private function selectFieldsSplitter(string $field, string ...$fields): callable
    {
        array_unshift($fields, $field);
        return
            /**
             * @param array<K,V> $record
             * @return array{0:array<string,V>,1:array<K,V>}
             */
            function (array $record) use ($fields): array {
                $item = [];
                foreach ($fields as &$field) {
                    if (!array_key_exists($field, $record)) {
                        throw new DatabaseException('Property "' . $field . '" not set in ' . var_export($record, true));
                    }
                    $item[$field] = $record[$field];
                    unset($record[$field]);
                }
                return [$item, $record];
            };
    }

    /**
     * @template K as array-key
     * @template V
     * @param string $keyField
     * @param string $valueField
     * @return callable(array<K,V|null>):array{0:array<V>,1:array<K,V|null>}
     */
    private function mapSplitter(string $keyField, string $valueField): callable
    {
        return
            /**
             * @param array<K,V|null> $record
             * @return array{0:array<V>,1:array<K,V|null>}
             */
            function (array $record) use ($keyField, $valueField): array {
                if (!array_key_exists($keyField, $record)) {
                    throw new DatabaseException('Property "' . $keyField . '" not set in ' . var_export($record, true));
                }
                $key = $record[$keyField];
                if (!(is_null($key) || is_scalar($key) || (is_object($key) && method_exists($key, '__toString')))) {
                    throw new DatabaseException('Cannot use field as key: ' . var_export($key, true));
                }
                if (!is_int($key)) {
                    $key = (string) $key;
                }
                if (!array_key_exists($valueField, $record)) {
                    throw new DatabaseException('Property "' . $valueField . '" not set in ' . var_export($record, true));
                }
                $item = [
                    $key => $record[$valueField]
                ];
                unset($record[$keyField]);
                unset($record[$valueField]);
                return [$item, $record];
            };
    }

    /**
     * @template K as array-key
     * @template V
     * @param string $prefix
     * @return callable(array<K,V>):array{0:array<string,V>,1:array<K,V>}
     */
    private function selectByPrefixSplitter(string $prefix): callable
    {
        $length = strlen($prefix);
        return
            /**
             * @param array<K,V> $record
             * @return array{0:array<string,V>,1:array<K,V>}
             */
            function (array $record) use ($prefix, $length): array {
                $item = [];
                foreach ($record as $field => &$value) {
                    if (!is_string($field)) {
                        continue;
                    }
                    assert(is_string($field));
                    if (isset(self::$prefixCache[$field][$prefix])) {
                        $suffix = self::$prefixCache[$field][$prefix];
                    } else {
                        if (strpos($field, $prefix) === 0) {
                            $suffix = substr($field, $length);
                        } else {
                            $suffix = false;
                        }
                        self::$prefixCache[$field][$prefix] = $suffix;
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
     * @template K as array-key
     * @template V
     * @return callable(array<K,V>):array{0:array<K,V>,1:array<K,V>}
     */
    private function selectAllSplitter(): callable
    {
        return
            /**
             * @param array<K,V> $record
             * @return array{0:array<K,V>,1:array<K,V>}
             */
            function (array $record): array {
                return [$record, []];
            };
    }

    /**
     * @template K as int|string
     * @template V
     * @param string $field
     * @param string ...$fields
     * @return callable(array<K,V>):array{0:V|null,1:array<K,V>}
     */
    private function coalesceSplitter(string $field, string ...$fields): callable
    {
        array_unshift($fields, $field);
        return
            /**
             * @param array<K,V> $record
             * @return array{0:V|null,1:array<K,V>}
             */
            function (array $record) use ($fields): array {
                $item = null;
                foreach ($fields as &$field) {
                    if ($item === null) {
                        if (!array_key_exists($field, $record)) {
                            throw new DatabaseException('Property "' . $field . '" not set in ' . var_export($record, true));
                        }
                        $item = $record[$field];
                    }
                    unset($record[$field]);
                }
                return [$item, $record];
            };
    }
}
