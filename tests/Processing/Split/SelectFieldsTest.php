<?php

namespace Hamlet\Database\Processing\Split;

use Hamlet\Database\DatabaseException;
use PHPUnit\Framework\TestCase;

class SelectFieldsTest extends TestCase
{
    public function test_non_existing_field_triggers_an_exception()
    {
        $record = [
            'a' => 1,
            'b' => 2,
        ];
        $splitter = new SelectFields(['a', 'c']);
        $this->expectException(DatabaseException::class);
        $splitter->apply($record);
    }

    public function test_fields_are_taken_and_original_record_reduced()
    {
        $record = [
            'a' => 1,
            'b' => 2,
            'c' => 3,
        ];
        $splitter = new SelectFields(['b', 'c']);
        $this->assertSame([['b' => 2, 'c' => 3], ['a' => 1]], $splitter->apply($record));
    }
}