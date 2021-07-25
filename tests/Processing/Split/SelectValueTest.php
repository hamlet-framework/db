<?php

namespace Hamlet\Database\Processing\Split;

use Hamlet\Database\DatabaseException;
use PHPUnit\Framework\TestCase;

class SelectValueTest extends TestCase
{
    public function test_non_existing_field_triggers_an_exception()
    {
        $record = [
            'a' => 1,
            'b' => 2
        ];
        $splitter = new SelectValue('c');
        $this->expectException(DatabaseException::class);
        $splitter($record);
    }

    public function test_value_is_taken_and_original_record_reduced()
    {
        $record = [
            'a' => 1,
            'b' => 2,
        ];
        $splitter = new SelectValue('b');
        $this->assertSame([2, ['a' => 1]], $splitter($record));
    }
}
