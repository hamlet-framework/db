<?php

namespace Hamlet\Database;

use Exception;
use Hamlet\Database\Batches\Batch;
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

    /**
     * @template K
     * @template Q
     * @param callable[] $callables
     * @psalm-param array<K,callable(Session):Q> $callables
     * @return array
     * @psalm-return array<K,Q>
     */
    public function withSessions(array $callables)
    {
        $result = [];
        foreach ($callables as $key => $callable) {
            $result[$key] = $this->withSession($callable);
        }
        return $result;
    }

    /**
     * @template Q
     * @param Batch $batch
     * @psalm-param Batch<Q> $batch
     * @return array
     * @psalm-suppress MissingClosureReturnType
     */
    public function processBatch(Batch $batch): array
    {
        $callables = [];
        foreach ($batch->items() as $key => $item) {
            $callables[$key] = function (Session $session) use ($batch, $item) {
                return $batch->apply($item($session));
            };
        }
        return $this->withSessions($callables);
    }
}
