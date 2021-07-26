<?php

namespace Hamlet\Database\Processing\Merge;

use Generator;
use Hamlet\Cast\CastException;
use Hamlet\Database\Processing\Split\SelectFields;
use PHPUnit\Framework\TestCase;

class CastTest extends TestCase
{
    public function test_cast()
    {
        $records = function (): Generator {
            yield from [
                [
                    'name' => 'mary',
                    'married' => true
                ],
                [
                    'name' => 'joseph',
                    'married' => true
                ]
            ];
        };
        $split = new SelectFields(['name']);
        $merge = new Cast(A::class);
        $result = iterator_to_array($merge($split->transform($records())));

        $this->assertCount(2, $result);
        $this->assertInstanceOf(A::class, $result[0]);
        $this->assertInstanceOf(A::class, $result[1]);
        $this->assertEquals('mary', $result[0]->name);
        $this->assertEquals('joseph', $result[1]->name);
    }

    public function test_exception_is_thrown_on_impossible_cast()
    {
        $records = function (): Generator {
            yield from [
                [
                    'firstName' => 'mary'
                ]
            ];
        };
        $split = new SelectFields(['firstName']);
        $merge = new Cast(A::class);

        $this->expectException(CastException::class);

        print_r(iterator_to_array($merge($records(), $split)));
        $merge($records(), $split);
    }
}
