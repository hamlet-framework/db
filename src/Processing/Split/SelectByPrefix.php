<?php declare(strict_types=1);

namespace Hamlet\Database\Processing\Split;

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
     * @return array{0:array<string,V>,1:array<K,V>}
     */
    public function __invoke(array $record): array
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
}
