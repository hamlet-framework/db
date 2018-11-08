<?php

namespace Hamlet\Database\Traits;

trait QueryExpanderTrait
{
    /**
     * @param string $query
     * @param array<array{0:string,1:string|float|int|array<string>|array<float>|array<int>|null}> $parameters
     * @return array{0:string,1:array<array{0:string,1:int|string|float|null}>}
     */
    private function unwrapQueryAndParameters(string $query, array $parameters): array
    {
        $unwrappedQuery = $query;
        $unwrappedParameters = [];

        if (!empty($parameters)) {
            $position = 0;
            $counter = 0;
            while (true) {
                $position = \strpos($unwrappedQuery, '?', $position);
                if ($position === false) {
                    break;
                }
                $value = $parameters[$counter][1];
                if (is_array($value)) {
                    //
                    // @todo
                    // this is more complex. (?) as well all FIELD(XML, ?) should be valid usages
                    // as well as ? inside of a string literal
                    //
                    $in = '(' . \join(', ', \array_fill(0, \count($value), '?')) . ')';
                    $unwrappedQuery = \substr($unwrappedQuery, 0, $position) . $in . \substr($unwrappedQuery, $position + 1);
                    foreach ($value as $i) {
                        $unwrappedParameters[] = [$parameters[$counter][0], $i];
                    }
                    $position += \strlen($in);
                } else {
                    $unwrappedParameters[] = $parameters[$counter];
                    $position++;
                }
                $counter++;
            }
        }
        return [$unwrappedQuery, $unwrappedParameters];
    }
}
