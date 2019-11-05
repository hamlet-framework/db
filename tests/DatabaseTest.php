<?php

/** @noinspection PhpUndefinedMethodInspection */

namespace Hamlet\Database;

use Phake;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

class DatabaseTest extends TestCase
{
    public function testTransactionRolledBackOnExceptionAndTheConnectionIsReturnedIntoPool()
    {
        $connection = new stdClass;
        $pool = Phake::mock(SimpleConnectionPool::class);
        Phake::when($pool)->pop()->thenReturn($connection);

        $database = Phake::partialMock(Database::class, $pool);

        $exceptionThrown = false;
        try {
            $database->withTransaction(function () {
                throw new RuntimeException();
            });
        } catch (DatabaseException $exception) {
            $exceptionThrown = true;
        }

        Assert::assertTrue($exceptionThrown);

        Phake::inOrder(
            Phake::verify($pool)->pop(),
            Phake::verify($database)->startTransaction($connection),
            Phake::verify($database)->rollback($connection),
            Phake::verify($pool)->push($connection)
        );
    }

    public function testNestedCallsReuseTransactions()
    {
        $connection = new stdClass;
        $pool = Phake::mock(SimpleConnectionPool::class);
        Phake::when($pool)->pop()->thenReturn($connection);

        $database = Phake::partialMock(Database::class, $pool);

        $database->withTransaction(function () use ($database) {
            $database->withTransaction(function () use ($database) {
                $database->withTransaction(function () {
                });
            });
        });

        Phake::inOrder(
            Phake::verify($pool)->pop(),
            Phake::verify($database)->startTransaction($connection),
            Phake::verify($database)->commit($connection),
            Phake::verify($pool)->push($connection)
        );
    }

    public function testWithDedicatedConnectionFetchesConnectionOnlyOnce()
    {
        $connection = new stdClass;
        $pool = Phake::mock(SimpleConnectionPool::class);
        Phake::when($pool)->pop()->thenReturn($connection);

        $database = Phake::partialMock(Database::class, $pool);

        $database->withDedicatedConnection(function () use ($database) {
            $database->withDedicatedConnection(function () use ($database) {
                $database->withDedicatedConnection(function () {
                });
            });
        });

        Phake::inOrder(
            Phake::verify($pool)->pop(),
            Phake::verify($pool)->push($connection)
        );

        Phake::verify($pool, Phake::times(1))->pop();
        Phake::verify($pool, Phake::times(1))->push($connection);
    }

    public function testTransactionsRespectDedicatedConnections()
    {
        $connection = new stdClass;
        $pool = Phake::mock(SimpleConnectionPool::class);
        Phake::when($pool)->pop()->thenReturn($connection);

        $database = Phake::partialMock(Database::class, $pool);

        $database->withDedicatedConnection(function () use ($database) {
            $database->withTransaction(function () use ($database) {
            });
        });

        Phake::inOrder(
            Phake::verify($pool)->pop(),
            Phake::verify($database)->startTransaction($connection),
            Phake::verify($database)->commit($connection),
            Phake::verify($pool)->push($connection)
        );

        Phake::verify($pool, Phake::times(1))->pop();
        Phake::verify($pool, Phake::times(1))->push($connection);
    }

    public function testDedicatedConnectionsRespectTransactions()
    {
        $connection = new stdClass;
        $pool = Phake::mock(SimpleConnectionPool::class);
        Phake::when($pool)->pop()->thenReturn($connection);

        $database = Phake::partialMock(Database::class, $pool);

        $database->withTransaction(function () use ($database) {
            $database->withDedicatedConnection(function () use ($database) {
            });
        });

        Phake::inOrder(
            Phake::verify($pool)->pop(),
            Phake::verify($database)->startTransaction($connection),
            Phake::verify($database)->commit($connection),
            Phake::verify($pool)->push($connection)
        );

        Phake::verify($pool, Phake::times(1))->pop();
        Phake::verify($pool, Phake::times(1))->push($connection);
    }
}
