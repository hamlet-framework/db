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
     * @var ConnectionPool
     * @psalm-var ConnectionPool<T>
     */
    protected $pool;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param ConnectionPool $pool
     * @psalm-param ConnectionPool<T> $pool
     */
    protected function __construct(ConnectionPool $pool)
    {
        $this->pool = $pool;
        $this->logger = new NullLogger;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->pool->setLogger($logger);
    }

    /**
     * @param mixed $handle
     * @psalm-param T $handle
     * @return Session
     * @psalm-return Session<T>
     */
    abstract protected function createSession($handle): Session;

    /**
     * @template Q
     * @param callable $callable
     * @psalm-param callable(Session):Q $callable
     * @return mixed
     * @psalm-return Q
     */
    public function withSession(callable $callable)
    {
        $handle = $this->pool->pop();
        $session = $this->createSession($handle);
        try {
            $result = $callable($session);
            return $result;
        } catch (DatabaseException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new DatabaseException('Failed to execute statement', 0, $e);
        } finally {
            $this->pool->push($handle);
        }
    }
}
