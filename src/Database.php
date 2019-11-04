<?php

namespace Hamlet\Database;

use Exception;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @template T
 */
abstract class Database implements LoggerAwareInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var mixed
     * @psalm-var T|null
     */
    private $pinnedConnection = null;

    /**
     * @var ConnectionPool
     * @psalm-var ConnectionPool<T>
     */
    protected $pool;

    /**
     * @param ConnectionPool $pool
     * @psalm-param ConnectionPool<T> $pool
     */
    protected function __construct(ConnectionPool $pool)
    {
        $this->pool = $pool;
        $this->logger = new NullLogger();
    }

    /**
     * @param string $query
     * @return Procedure
     */
    abstract public function prepare(string $query): Procedure;

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->pool->setLogger($logger);
    }

    /**
     * @template Q
     * @param callable $callable
     * @psalm-param callable():Q $callable
     * @return mixed
     * @psalm-return Q
     */
    public function withDedicatedConnection(callable $callable)
    {
        try {
            $nested = ($this->getPinnedConnection() !== null);
            if (!$nested) {
                $this->setPinnedConnection($this->pool->pop());
            }
            $result = $callable();
            if (!$nested) {
                assert($this->getPinnedConnection() !== null);
                $this->pool->push($this->getPinnedConnection());
                $this->setPinnedConnection(null);
            }
            return $result;
        } catch (Exception $e) {
            if ($this->getPinnedConnection() !== null) {
                $this->pool->push($this->getPinnedConnection());
                $this->setPinnedConnection(null);
            }
            throw new DatabaseException('Failed to execute statement', 0, $e);
        }
    }

    /**
     * @template Q
     * @param callable $callable
     * @psalm-param callable():Q $callable
     * @return mixed
     * @psalm-return Q
     */
    public function withTransaction(callable $callable)
    {
        try {
            $nested = ($this->getPinnedConnection() !== null);
            if ($nested) {
                $this->logger->debug('Transaction already started');
            } else {
                $this->setPinnedConnection($this->pool->pop());
                $this->startTransaction($this->getPinnedConnection());
            }
            $result = $callable();
            if (!$nested) {
                assert($this->getPinnedConnection() !== null);
                $this->commit($this->getPinnedConnection());
                $this->pool->push($this->getPinnedConnection());
                $this->setPinnedConnection(null);
            }
            return $result;
        } catch (Exception $e) {
            if ($this->getPinnedConnection() !== null) {
                try {
                    $this->rollback($this->getPinnedConnection());
                } catch (Exception $e1) {
                    throw new DatabaseException('Cannot rollback transaction', 0, $e1);
                } finally {
                    $this->pool->push($this->getPinnedConnection());
                    $this->setPinnedConnection(null);
                }
            }
            throw new DatabaseException('Transaction failed', 0, $e);
        }
    }

    /**
     * @return mixed
     * @psalm-return T|null
     */
    protected function getPinnedConnection()
    {
        return $this->pinnedConnection;
    }

    /**
     * @param mixed $connection
     * @psalm-param T|null $connection
     */
    protected function setPinnedConnection($connection)
    {
        $this->pinnedConnection = $connection;
    }

    /**
     * @template Q
     * @return callable
     * @psalm-return callable(callable(T):Q):Q
     */
    protected function executor()
    {
        return
            /**
             * @template Q
             * @param callable $callback
             * @psalm-param callable(T):Q $callback
             * @return mixed
             * @psalm-return Q
             */
            function (callable $callback) {
                if ($this->pinnedConnection) {
                    $this->logger->debug('Executing using pinned connection');
                    return ($callback)($this->pinnedConnection);
                }

                $connection = $this->pool->pop();
                try {
                    $this->logger->debug('Executing using a new connection from pool');
                    return ($callback)($connection);
                } catch (DatabaseException $exception) {
                    throw $exception;
                } catch (Exception $exception) {
                    throw new DatabaseException('Database exception', 0, $exception);
                } finally {
                    $this->pool->push($connection);
                }
            };
    }

    /**
     * @param mixed $connection
     * @psalm-param T $connection
     * @return void
     */
    abstract protected function startTransaction($connection);

    /**
     * @param mixed $connection
     * @psalm-param T $connection
     * @return void
     */
    abstract protected function commit($connection);

    /**
     * @param mixed $connection
     * @psalm-param T $connection
     * @return void
     */
    abstract protected function rollback($connection);
}
