<?php

/** @noinspection PhpUndefinedMethodInspection */

namespace Hamlet\Database;

use Hamlet\Database\Processing\BatchProcessor;
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

        $session = Phake::partialMock(Session::class, $connection);
        $database = Phake::partialMock(Database::class, $pool);
        Phake::when($database)->createSession($connection)->thenReturn($session);

        $exceptionThrown = false;
        try {
            $database->withSession(function ($session) {
                $session->withTransaction(function () {
                });
                $session->withTransaction(function () {
                });
                $session->withTransaction(function () {
                    throw new RuntimeException();
                });
            });
        } catch (DatabaseException $exception) {
            $exceptionThrown = true;
        }

        Assert::assertTrue($exceptionThrown);

        Phake::inOrder(
            Phake::verify($pool)->pop(),
            Phake::verify($session, Phake::times(3))->startTransaction($connection),
            Phake::verify($session, Phake::times(2))->commit($connection),
            Phake::verify($session, Phake::times(1))->rollback($connection),
            Phake::verify($pool)->push($connection)
        );
    }

    public function testNestedCallsReuseTransactions()
    {
        $connection = new stdClass;
        $pool = Phake::mock(SimpleConnectionPool::class);
        Phake::when($pool)->pop()->thenReturn($connection);

        $session = Phake::partialMock(Session::class, $connection);
        $database = Phake::partialMock(Database::class, $pool);
        Phake::when($database)->createSession($connection)->thenReturn($session);

        $database->withSession(function ($session) {
            $session->withTransaction(function () use ($session) {
                $session->withTransaction(function () use ($session) {
                    $session->withTransaction(function () {
                    });
                });
            });
        });

        Phake::inOrder(
            Phake::verify($pool)->pop(),
            Phake::verify($session)->startTransaction($connection),
            Phake::verify($session)->commit($connection),
            Phake::verify($pool)->push($connection)
        );
    }

    public function testWithSessionPropagatesValue()
    {
        $connection = new stdClass;
        $pool = Phake::mock(SimpleConnectionPool::class);
        Phake::when($pool)->pop()->thenReturn($connection);

        $session = Phake::partialMock(Session::class, $connection);
        $database = Phake::partialMock(Database::class, $pool);
        Phake::when($database)->createSession($connection)->thenReturn($session);

        $result = $database->withSession(function ($session) {
            return $session->withTransaction(function () use ($session) {
                return $session->withTransaction(function () use ($session) {
                    return $session->withTransaction(function () {
                        return 42;
                    });
                });
            });
        });

        Assert::assertEquals(42, $result);
    }
}
