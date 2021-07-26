<?php

namespace Hamlet\Database\Processing\Split;

use Hamlet\Database\DatabaseException;
use PHPUnit\Framework\TestCase;

class CoalesceTest extends TestCase
{
    public function test_exception_is_thrown_on_non_existing_field()
    {
        $record = [
            'a' => 1,
            'b' => 2,
        ];
        $splitter = new Coalesce(['c', 'a']);
        $this->expectException(DatabaseException::class);
        $splitter->apply($record);
    }

    public function test_null_values_are_skipped()
    {
        $record = [
            'a' => null,
            'b' => 1
        ];
        $splitter = new Coalesce(['a', 'b']);
        $this->assertEquals([1, []], $splitter->apply($record));
    }

    public function test_remaining_fields_are_removed()
    {
        $record = [
            'a' => 1,
            'b' => 2,
            'c' => 3
        ];
        $splitter = new Coalesce(['a', 'b']);
        $this->assertEquals([1, ['c' => 3]], $splitter->apply($record));
    }
}
