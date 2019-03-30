<?php

namespace Hamlet\Database;

use Exception;
use InvalidArgumentException;
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
     * @var T|null
     */
    private $pinnedConnection = null;

    /**
     * @var ConnectionPool<T>
     */
    private $pool;

    /**
     * @param ConnectionPool<T> $pool
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
     * @return callable(callable(T):Q):Q
     */
    protected function executor()
    {
        return
            /**
             * @template Q
             * @param callable(T):Q $callback
             * @return Q
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
     * @template Q
     * @param callable():Q $callable
     * @return Q
     */
    public function withTransaction(callable $callable)
    {
        try {
            $nested = ($this->pinnedConnection !== null);
            if ($nested) {
                $this->logger->debug('Transaction already started');
            } else {
                $this->pinnedConnection = $this->pool->pop();
                $this->startTransaction($this->pinnedConnection);
            }
            $result = $callable();
            if (!$nested) {
                assert($this->pinnedConnection !== null);
                $this->commit($this->pinnedConnection);
                $this->pool->push($this->pinnedConnection);
                $this->pinnedConnection = null;
            }
            return $result;
        } catch (Exception $e) {
            if ($this->pinnedConnection !== null) {
                try {
                    $this->rollback($this->pinnedConnection);
                } catch (Exception $e1) {
                    throw new DatabaseException('Cannot rollback transaction', 0, $e1);
                } finally {
                    $this->pool->push($this->pinnedConnection);
                    $this->pinnedConnection = null;
                }
            }
            throw new DatabaseException('Transaction failed', 0, $e);
        }
    }

    /**
     * @template Q
     * @param callable():Q $callable
     * @param int $maxAttempts
     * @return Q
     */
    public function tryWithTransaction(callable $callable, int $maxAttempts)
    {
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                return $this->withTransaction($callable);
            } catch (DatabaseException $e) {
                if ($attempt == $maxAttempts) {
                    throw new DatabaseException('Transaction failed after ' . $attempt . ' attempt(s)', 0, $e);
                }
            }
        }
        throw new InvalidArgumentException('Number of attempts must be greater than 0');
    }

    /**
     * @param T $connection
     * @return void
     */
    abstract protected function startTransaction($connection);

    /**
     * @param T $connection
     * @return void
     */
    abstract protected function commit($connection);

    /**
     * @param T $connection
     * @return void
     */
    abstract protected function rollback($connection);
}
