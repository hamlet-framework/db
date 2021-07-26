<?php

namespace Hamlet\Database\Processing\Split;

use DateTimeImmutable;
use Hamlet\Database\DatabaseException;
use PHPUnit\Framework\TestCase;

class MapTest extends TestCase
{
    public function test_non_existing_key_field_triggers_exception()
    {
        $record = [
            'a' => '1',
            'b' => '2'
        ];
        $splitter = new Map('c', 'a');
        $this->expectException(DatabaseException::class);
        $splitter->apply($record);
    }

    public function test_invalid_key_value_throws_exception()
    {
        $record = [
            'a' => new DateTimeImmutable,
            'b' => 1
        ];
        $splitter = new Map('a', 'b');
        $this->expectException(DatabaseException::class);
        $splitter->apply($record);
    }

    public function test_value_non_integer_keys_are_converted_to_strings()
    {
        $record = [
            'a' => 0.123,
            'b' => 'mary'
        ];
        $splitter = new Map('a', 'b');
        $this->assertEquals([['0.123' => 'mary'], []], $splitter->apply($record));
    }

    public function test_non_existing_value_field_triggers_exception()
    {
        $record = [
            'a' => '1',
            'b' => '2'
        ];
        $splitter = new Map('a', 'c');
        $this->expectException(DatabaseException::class);
        $splitter->apply($record);
    }

    public function test_fields_are_taken_and_original_record_reduced()
    {
        $record = [
            'c' => 'C',
            'a' => 'A',
            'b' => 'B'
        ];
        $splitter = new Map('b', 'a');
        $this->assertEquals([['B' => 'A'], ['c' => 'C']], $splitter->apply($record));
    }
}
