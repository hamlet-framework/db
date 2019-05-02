<?php

namespace Hamlet\Database;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class ConnectionPoolTest extends TestCase
{
    public function testFetchingFromAnEmptyPoolTriggersNewConnection()
    {
        $connectorCallCount = 0;
        $connector = function () use (&$connectorCallCount) {
            $connectorCallCount++;
        };
        $pool = new ConnectionPool($connector);
        $pool->pop();

        Assert::assertEquals(1, $connectorCallCount);
    }

    public function testConnectionsGetReused()
    {
        $connectorCallCount = 0;
        $connector = function () use (&$connectorCallCount) {
            $connectorCallCount++;
        };
        $pool = new ConnectionPool($connector);

        $connection = $pool->pop();
        $pool->push($connection);
        $pool->pop();

        Assert::assertEquals(1, $connectorCallCount);
    }
}
