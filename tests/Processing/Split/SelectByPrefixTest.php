<?php

namespace Hamlet\Database\Processing\Split;

use PHPUnit\Framework\TestCase;

class SelectByPrefixTest extends TestCase
{
    public function test_integer_keys_are_skipped()
    {
        $record = [
            1 => 'A',
            '1b' => 'B',
            '1c' => 'C',
        ];
        $splitter = new SelectByPrefix('1');
        $this->assertEquals([['b' => 'B', 'c' => 'C'], [1 => 'A']], $splitter($record));
    }

    public function test_fields_are_taken_and_original_record_reduced()
    {
        $record = [
            'f_a' => 'A',
            'f_b' => 'B',
            'c' => 'C'
        ];
        $splitter = new SelectByPrefix('f_');
        $this->assertEquals([['a' => 'A', 'b' => 'B'], ['c' => 'C']], $splitter($record));

        // apply again to see if the cache is working
        $this->assertEquals([['a' => 'A', 'b' => 'B'], ['c' => 'C']], $splitter($record));
    }
}
