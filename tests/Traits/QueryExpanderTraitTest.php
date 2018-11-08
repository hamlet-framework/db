<?php

namespace Hamlet\Database\Traits;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class QueryExpanderTraitTest extends TestCase
{
    use QueryExpanderTrait;

    public function testSimpleAtomExpansion()
    {
        $query = 'SELECT * FROM table WHERE field = ?';
        $parameters = [['s', 'value']];

        list($unwrappedQuery, $unwrappedParameters) = $this->unwrapQueryAndParameters($query, $parameters);

        Assert::assertEquals($query, $unwrappedQuery);
        Assert::assertEquals($parameters, $unwrappedParameters);
    }

    public function testSimpleArrayExpansion()
    {
        $query = 'SELECT * FROM table WHERE field IN ?';
        $parameters = [['s', ['v1', 'v2']]];

        list($unwrappedQuery, $unwrappedParameters) = $this->unwrapQueryAndParameters($query, $parameters);

        Assert::assertEquals('SELECT * FROM table WHERE field IN (?, ?)', $unwrappedQuery);
        Assert::assertEquals([['s', 'v1'], ['s', 'v2']], $unwrappedParameters);
    }
}
