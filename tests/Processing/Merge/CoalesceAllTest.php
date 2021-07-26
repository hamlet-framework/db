<?php

namespace Hamlet\Database\Processing\Merge;

use Generator;
use PHPUnit\Framework\TestCase;

class CoalesceAllTest extends TestCase
{
    public function test_coalesce()
    {
        $records = function (): Generator {
            yield from [
                [
                    'a' => 1,
                    'b' => 2
                ],
                [
                    'b' => null,
                    'a' => 4
                ],
                [
                    'c' => null,
                    'd' => null
                ]
            ];
        };
        $merge = new CoalesceAll();
        $result = $merge($records());

        $this->assertEquals([1, 4], iterator_to_array($result));
    }
}
