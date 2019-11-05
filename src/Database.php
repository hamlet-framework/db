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
    private $dedicatedConnection = null;

    /**
     * @var bool
     */
    private $transactionStarted = false;

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
        $nested = ($this->getDedicatedConnection() !== null);
        if (!$nested) {
            $this->setDedicatedConnection($this->pool->pop());
        }
        try {
            return $callable();
        } catch (DatabaseException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new DatabaseException('Failed to execute statement', 0, $e);
        } finally {
            if (!$nested) {
                $connection = $this->getDedicatedConnection();
                assert($connection !== null);
                $this->pool->push($connection);
                $this->setDedicatedConnection(null);
            }
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
        $newTransaction = !$this->transactionStarted;
        $transactionOwnsConnection = false;
        try {
            if ($newTransaction) {
                if ($this->getDedicatedConnection() === null) {
                    $transactionOwnsConnection = true;
                    $this->setDedicatedConnection($this->pool->pop());
                }
                $this->startTransaction($this->getDedicatedConnection());
                $this->transactionStarted = true;
            } else {
                $this->logger->debug('Transaction already started');
            }
            return $callable();
        } catch (DatabaseException $e) {
            $this->rollback($this->getDedicatedConnection());
            throw $e;
        } catch (Exception $e) {
            $this->rollback($this->getDedicatedConnection());
            throw new DatabaseException('Failed to execute statement', 0, $e);
        } finally {
            if ($newTransaction) {
                $this->commit($this->getDedicatedConnection());
                $this->transactionStarted = false;
                if ($transactionOwnsConnection) {
                    $connection = $this->getDedicatedConnection();
                    assert($connection !== null);
                    $this->setDedicatedConnection(null);
                    $this->pool->push($connection);
                }
            }
        }
    }

    /**
     * @return mixed
     * @psalm-return T|null
     */
    protected function getDedicatedConnection()
    {
        return $this->dedicatedConnection;
    }

    /**
     * @param mixed $connection
     * @psalm-param T|null $connection
     * @return void
     */
    protected function setDedicatedConnection($connection)
    {
        $this->dedicatedConnection = $connection;
    }

    /**
     * @template Q
     * @return callable
     * @psalm-return callable(callable(T):Q):Q
     */
    protected function executor()
    {
        $connection = $this->getDedicatedConnection();
        if ($connection) {
            return static function (callable $callback) use ($connection) {
                try {
                    $this->logger->debug('Executing using dedicated connection');
                    return ($callback)($connection);
                } catch (DatabaseException $exception) {
                    throw $exception;
                } catch (Exception $exception) {
                    throw new DatabaseException('Database exception', 0, $exception);
                }
            };
        } else {
            return function (callable $callback) use ($connection) {
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
