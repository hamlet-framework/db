<?php

namespace Hamlet\Database\Processing\Split;

use PHPUnit\Framework\TestCase;

class SelectAllTest extends TestCase
{
    public function test_complete_record_is_taken()
    {
        $record = [
            'a' => 1,
            'b' => 2,
            'c' => null
        ];
        $splitter = new SelectAll;
        $this->assertSame([$record, []], $splitter($record));
    }
}
