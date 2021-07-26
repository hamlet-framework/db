<?php

namespace Hamlet\Database\Processing\Merge;

use Generator;
use Hamlet\Database\Processing\Split\SelectValue;
use PHPUnit\Framework\TestCase;

class GroupStreamedTest extends TestCase
{
    public function test_group_streamed_unordered_records()
    {
        $records = function (): Generator {
            yield from [
                [
                    'name' => 'jack',
                    'card' => 'king'
                ],
                [
                    'name' => 'jack',
                    'card' => '8'
                ],
                [
                    'name' => 'jane',
                    'card' => 'queen'
                ],
                [
                    'name' => 'jack',
                    'card' => '10'
                ]
            ];
        };
        $split = new SelectValue('card');
        $merge = new GroupStreamed();

        $result = iterator_to_array($merge->transform($split->transform($records())));

        $this->assertEquals([0 => ['king', '8'], 2 => ['queen'], 3 => ['10']], $result);
    }
}
