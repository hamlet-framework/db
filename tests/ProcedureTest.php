<?php

namespace Hamlet\Database;

use Generator;
use PHPUnit\Framework\TestCase;

class ProcedureTest extends TestCase
{
    public function testParameterCapture()
    {
        $procedure = new class() extends Procedure
        {
            protected function fetch(): Generator
            {
                return new Generator();
            }

            public function insert(): int
            {
                return 0;
            }

            public function execute(): void {}

            public function affectedRows(): int
            {
                return 0;
            }

            public function parameters(): array
            {
                return $this->parameters;
            }
        };

        $procedure->bindInteger(1);
        $procedure->bindBlob('blob');
        $procedure->bindFloat(0.43);
        $procedure->bindString('abc');
        $procedure->bindIntegerList([1, 2, 3]);
        $procedure->bindFloatList([0.1, 0.2, 0.3]);
        $procedure->bindStringList(['a', 'b', 'c']);
        $procedure->bindNullableInteger(null);
        $procedure->bindNullableBlob(null);
        $procedure->bindNullableFloat(null);
        $procedure->bindNullableString(null);

        $this->assertEquals([
            ['i', 1],
            ['b', 'blob'],
            ['d', 0.43],
            ['s', 'abc'],
            ['i', [1, 2, 3]],
            ['d', [0.1, 0.2, 0.3]],
            ['s', ['a', 'b', 'c']],
            ['i', null],
            ['b', null],
            ['d', null],
            ['s', null],
        ], $procedure->parameters());
    }
}
