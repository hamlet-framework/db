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
     * @var mixed
     * @psalm-var T|null
     */
    private $pinnedConnection = null;

    /**
     * @var callable[]
     * @psalm-var array<string,callable():void>
     */
    private $onSuccess = [];

    /**
     * @var ConnectionPoolInterface
     * @psalm-var ConnectionPoolInterface<T>
     */
    protected $pool;

    /**
     * @param ConnectionPoolInterface $pool
     * @psalm-param ConnectionPool<T> $pool
     */
    protected function __construct(ConnectionPoolInterface $pool)
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
     *
     * @return       callable
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
     * @template Q
     *
     * @param       callable                      $callable
     * @psalm-param callable():Q                  $callable
     *
     * @param       callable[]                    $onSuccess
     * @psalm-param array<string,callable():void> $onSuccess
     *
     * @return       mixed
     * @psalm-return Q
     */
    public function withTransaction(callable $callable, array $onSuccess = [])
    {
        try {
            $nested = ($this->pinnedConnection !== null);
            if ($nested) {
                $this->logger->debug('Transaction already started');
            } else {
                $this->pinnedConnection = $this->pool->pop();
                $this->startTransaction($this->pinnedConnection);
            }
            $this->onSuccess = array_merge($this->onSuccess, $onSuccess);
            $result = $callable();
            if (!$nested) {
                assert($this->pinnedConnection !== null);
                $this->commit($this->pinnedConnection);
                $this->pool->push($this->pinnedConnection);
                foreach ($this->onSuccess as $callback) {
                    $callback();
                }
                $this->onSuccess = [];
                $this->pinnedConnection = null;
            }
            return $result;
        } catch (Exception $e) {
            $this->onSuccess = [];
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
     *
     * @param       callable                      $callable
     * @psalm-param callable():Q                  $callable
     *
     * @param       int                           $maxAttempts
     *
     * @param       callable[]                    $onSuccess
     * @psalm-param array<string,callable():void> $onSuccess
     *
     * @return       mixed
     * @psalm-return Q
     */
    public function tryWithTransaction(callable $callable, int $maxAttempts, array $onSuccess = [])
    {
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                return $this->withTransaction($callable, $onSuccess);
            } catch (DatabaseException $e) {
                if ($attempt == $maxAttempts) {
                    throw new DatabaseException('Transaction failed after ' . $attempt . ' attempt(s)', 0, $e);
                }
            }
        }
        throw new InvalidArgumentException('Number of attempts must be greater than 0');
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
