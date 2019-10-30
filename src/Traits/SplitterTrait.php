<?php

namespace Hamlet\Database\Traits;

use RuntimeException;
use function is_int;
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
                if (!array_key_exists($field, $record)) {
                    throw new RuntimeException('Property "' . $field . '" not set in ' . print_r($record, true));
                }
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
             * @psalm-return array{0:array<string,V>,1:array<K,V>}
             */
            function (array $record) use ($fields): array {
                $item = [];
                foreach ($fields as &$field) {
                    if (!array_key_exists($field, $record)) {
                        throw new RuntimeException('Property "' . $field . '" not set in ' . print_r($record, true));
                    }
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
     * @psalm-return callable(array<K,V>):array{0:array<V>,1:array<K,V>}
     */
    private function mapSplitter(string $keyField, string $valueField): callable
    {
        return
            /**
             * @param array $record
             * @psalm-param array<K,V> $record
             * @return array
             * @psalm-return array{0:array<V>,1:array<K,V>}
             */
            function (array $record) use ($keyField, $valueField): array {
                if (!array_key_exists($keyField, $record)) {
                    throw new RuntimeException('Property "' . $keyField . '" not set in ' . print_r($record, true));
                }
                $key = $record[$keyField];
                if (!is_int($key)) {
                    /** @psalm-suppress InvalidCast */
                    $key = (string) $key;
                }
                if (!array_key_exists($valueField, $record)) {
                    throw new RuntimeException('Property "' . $valueField . '" not set in ' . print_r($record, true));
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
                        if (!array_key_exists($field, $record)) {
                            throw new RuntimeException('Property "' . $field . '" not set in ' . print_r($record, true));
                        }
                        $item = $record[$field];
                    }
                    unset($record[$field]);
                }
                return [$item, $record];
            };
    }
}
